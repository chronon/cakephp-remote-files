<?php
declare(strict_types=1);

namespace RemoteFiles\Test\TestCase\Model\Behavior;

use Cake\Core\Configure;
use Cake\Core\Exception\CakeException;
use Cake\ORM\Table;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Laminas\Diactoros\StreamFactory;
use Laminas\Diactoros\UploadedFile;
use RemoteFiles\Lib\CloudflareImage;
use RemoteFiles\Test\TestApp\Lib\RecordingManager;
use RemoteFiles\Test\TestApp\Model\Behavior\TestUploadBehavior;
use RemoteFiles\Test\TestCase\RemoteFilesTestCase;

class UploadBehaviorTest extends RemoteFilesTestCase
{
    protected array $fixtures = ['plugin.RemoteFiles.Uploads'];

    protected Table $Uploads;

    /**
     * @var list<array{request: \Psr\Http\Message\RequestInterface}>
     */
    protected array $cloudflareRequests = [];

    protected function setUp(): void
    {
        parent::setUp();
        Configure::write('RemoteFiles.RemoteStorage', 'Recording');
        Configure::write('RemoteFiles.Recording.managerClass', RecordingManager::class);
        RecordingManager::reset();
        TestUploadBehavior::$cloudflare = null;
        $this->Uploads = $this->table(['filename' => []]);
    }

    /**
     * @param array<string, mixed> $config
     */
    protected function table(array $config): Table
    {
        $this->getTableLocator()->clear();
        $table = $this->getTableLocator()->get('Uploads');
        $table->addBehavior('Upload', ['className' => TestUploadBehavior::class] + $config);

        return $table;
    }

    protected function upload(int $error = UPLOAD_ERR_OK, string $name = 'Report.PDF'): UploadedFile
    {
        $stream = (new StreamFactory())->createStream('file contents');
        $stream->seek(5);

        return new UploadedFile($stream, 13, $error, $name, 'application/pdf');
    }

    protected function fakeCloudflare(int $responses): void
    {
        $this->cloudflareRequests = [];
        $queue = array_fill(0, $responses, new Response(200, [], '{"success":true}'));
        $stack = HandlerStack::create(new MockHandler($queue));
        $stack->push(Middleware::history($this->cloudflareRequests));
        TestUploadBehavior::$cloudflare = new CloudflareImage([], new Client(['handler' => $stack]));
    }

    public function testColumnTypeMapped(): void
    {
        $this->assertSame('remote.file', $this->Uploads->getSchema()->getColumnType('filename'));
        $this->assertSame('string', $this->Uploads->getSchema()->getColumnType('remote'));
    }

    public function testSaveUploadsFile(): void
    {
        $entity = $this->Uploads->newEntity(['filename' => $this->upload()]);

        $this->assertNotFalse($this->Uploads->save($entity));
        $this->assertSame('Report.PDF', $entity->get('filename'));
        $this->assertSame('PDF', $entity->get('extension'));
        $this->assertMatchesRegularExpression('/^uploads-[0-9a-f-]{36}$/', $entity->get('remote'));
        $this->assertSame(
            [$entity->get('remote') . '.PDF' => 'file contents'],
            RecordingManager::$writes,
        );

        $saved = $this->Uploads->get($entity->get('id'));
        $this->assertSame('Report.PDF', $saved->get('filename'));
    }

    public function testPrefixes(): void
    {
        Configure::write('RemoteFiles.globalPrefix', 'site/');
        $Uploads = $this->table(['filename' => ['prefix' => 'cert']]);
        $entity = $Uploads->newEntity(['filename' => $this->upload()]);

        $this->assertNotFalse($Uploads->save($entity));
        $this->assertStringStartsWith('site/cert-', $entity->get('remote'));
    }

    public function testCustomFieldNames(): void
    {
        $Uploads = $this->table(['remote' => ['remoteField' => 'filename', 'extField' => 'extension']]);
        $entity = $Uploads->newEntity(['remote' => $this->upload()]);

        $this->assertNotFalse($Uploads->save($entity));
        $this->assertSame('Report.PDF', $entity->get('remote'));
        $this->assertStringStartsWith('uploads-', $entity->get('filename'));
    }

    public function testUploadErrorThrows(): void
    {
        $entity = $this->Uploads->newEntity(['filename' => $this->upload(UPLOAD_ERR_PARTIAL)]);

        $this->expectException(CakeException::class);
        $this->Uploads->save($entity);
    }

    public function testRemoteWriteFailureThrows(): void
    {
        RecordingManager::$fail = true;
        $entity = $this->Uploads->newEntity(['filename' => $this->upload()]);

        $this->expectException(CakeException::class);
        $this->Uploads->save($entity);
    }

    public function testEmptyUploadKeepsValueWhenEmptyAllowed(): void
    {
        $this->Uploads->getValidator()->allowEmptyFile('filename');
        $entity = $this->Uploads->newEntity(['filename' => $this->upload(UPLOAD_ERR_NO_FILE)]);

        $this->assertFalse($entity->has('filename'));
    }

    public function testEmptyUploadKeptWhenEmptyNotAllowed(): void
    {
        $entity = $this->Uploads->newEntity(['filename' => $this->upload(UPLOAD_ERR_NO_FILE)]);

        $this->assertInstanceOf(UploadedFile::class, $entity->get('filename'));
    }

    public function testDeleteRemovesRemoteFile(): void
    {
        $entity = $this->Uploads->newEntity(['filename' => $this->upload()]);
        $this->Uploads->saveOrFail($entity);

        $this->assertTrue($this->Uploads->delete($entity));
        $this->assertSame([$entity->get('remote') . '.PDF'], RecordingManager::$deletes);
    }

    public function testDeleteWithoutRemoteSkipsRemoteCalls(): void
    {
        $Uploads = $this->table(['filename' => ['cloudflareImage' => true]]);
        $entity = $Uploads->newEntity(['filename' => 'legacy.pdf'], ['validate' => false]);
        $Uploads->saveOrFail($entity);

        $this->assertTrue($Uploads->delete($entity));
        $this->assertSame([], RecordingManager::$deletes);
    }

    public function testDeleteDisabled(): void
    {
        $Uploads = $this->table(['filename' => ['deleteEnabled' => false]]);
        $entity = $Uploads->newEntity(['filename' => $this->upload()]);
        $Uploads->saveOrFail($entity);

        $this->assertTrue($Uploads->delete($entity));
        $this->assertSame([], RecordingManager::$deletes);
    }

    public function testBehaviorOptionsAreNotFields(): void
    {
        $Uploads = $this->table(['priority' => 5, 'filename' => []]);
        $entity = $Uploads->newEntity(['filename' => $this->upload()]);

        $this->assertNotFalse($Uploads->save($entity));
        $this->assertCount(1, RecordingManager::$writes);
    }

    public function testCloudflareImageUploadAndDelete(): void
    {
        $this->fakeCloudflare(2);
        $Uploads = $this->table(['filename' => ['cloudflareImage' => true]]);
        $entity = $Uploads->newEntity(['filename' => $this->upload()]);
        $Uploads->saveOrFail($entity);
        $remote = $entity->get('remote');

        $upload = $this->cloudflareRequests[0]['request'];
        $this->assertSame('POST', $upload->getMethod());
        $body = (string)$upload->getBody();
        $this->assertStringContainsString("https://remote.test/{$remote}.PDF", $body);
        $this->assertStringContainsString($remote, $body);

        $this->assertTrue($Uploads->delete($entity));
        $delete = $this->cloudflareRequests[1]['request'];
        $this->assertSame('DELETE', $delete->getMethod());
        $this->assertStringEndsWith("/images/v1/{$remote}", (string)$delete->getUri());
    }
}
