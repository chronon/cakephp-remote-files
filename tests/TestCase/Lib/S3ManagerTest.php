<?php
declare(strict_types=1);

namespace RemoteFiles\Test\TestCase\Lib;

use Aws\CommandInterface;
use Aws\MockHandler;
use Aws\Result;
use Aws\S3\Exception\S3Exception;
use Cake\Core\Configure;
use RemoteFiles\Lib\S3Manager;
use RemoteFiles\Test\TestCase\RemoteFilesTestCase;

class S3ManagerTest extends RemoteFilesTestCase
{
    protected MockHandler $aws;

    protected function setUp(): void
    {
        parent::setUp();
        $this->aws = new MockHandler();
        Configure::write('RemoteFiles.S3.clientConfig.handler', $this->aws);
    }

    public function testGetRemoteUrl(): void
    {
        $this->assertSame(
            'https://test-bucket.s3.amazonaws.com/files/test/foo.pdf',
            (new S3Manager())->getRemoteUrl('foo.pdf'),
        );
    }

    public function testGetRemoteUrlWithoutPrefix(): void
    {
        Configure::write('RemoteFiles.S3.prefix', '');

        $this->assertSame('https://test-bucket.s3.amazonaws.com/foo.pdf', (new S3Manager())->getRemoteUrl('foo.pdf'));
    }

    public function testRemoteWrite(): void
    {
        $this->aws->append(new Result([]));

        $this->assertTrue((new S3Manager())->remoteWrite('foo.txt', 'contents'));
        $command = $this->aws->getLastCommand();
        $this->assertSame('PutObject', $command->getName());
        $this->assertSame('test-bucket', $command['Bucket']);
        $this->assertSame('files/test/foo.txt', $command['Key']);
    }

    public function testRemoteWriteFailure(): void
    {
        $this->aws->append(fn(CommandInterface $command) => new S3Exception('denied', $command));

        $this->assertFalse((new S3Manager())->remoteWrite('foo.txt', 'contents'));
    }

    public function testRemoteDelete(): void
    {
        $this->aws->append(new Result([]));

        $this->assertTrue((new S3Manager())->remoteDelete('foo.txt'));
        $command = $this->aws->getLastCommand();
        $this->assertSame('DeleteObject', $command->getName());
        $this->assertSame('files/test/foo.txt', $command['Key']);
    }

    public function testRemoteDeleteFailure(): void
    {
        $this->aws->append(fn(CommandInterface $command) => new S3Exception('denied', $command));

        $this->assertFalse((new S3Manager())->remoteDelete('foo.txt'));
    }
}
