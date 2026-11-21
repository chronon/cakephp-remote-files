<?php
declare(strict_types=1);

namespace RemoteFiles\Test\TestCase\Lib;

use Cake\Core\Configure;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use RemoteFiles\Lib\CloudflareImage;
use RemoteFiles\Test\TestCase\RemoteFilesTestCase;

class CloudflareImageTest extends RemoteFilesTestCase
{
    /**
     * @var list<array{request: \Psr\Http\Message\RequestInterface}>
     */
    protected array $history = [];

    /**
     * @param list<\Psr\Http\Message\ResponseInterface|\Throwable> $queue
     * @param array{token?: string, account?: string, apiUrl?: string} $config
     */
    protected function cloudflare(array $queue, array $config = []): CloudflareImage
    {
        $this->history = [];
        $stack = HandlerStack::create(new MockHandler($queue));
        $stack->push(Middleware::history($this->history));

        return new CloudflareImage($config, new Client(['handler' => $stack]));
    }

    protected function lastRequest(): RequestInterface
    {
        $this->assertCount(1, $this->history);

        return $this->history[0]['request'];
    }

    public function testDefaultClient(): void
    {
        $this->assertInstanceOf(Client::class, (new CloudflareImage())->client);
    }

    public function testMissingConfigThrows(): void
    {
        Configure::delete('RemoteFiles.Cloudflare.Images.Auth.token');

        $this->expectException(InvalidArgumentException::class);
        new CloudflareImage();
    }

    public function testConfigOverridesAndEmptyValuesFallBack(): void
    {
        $cloudflare = $this->cloudflare(
            [new Response(200, [], '{"success":true}')],
            ['token' => 'override-token', 'account' => '', 'apiUrl' => 'https://cf.test/%s/images'],
        );

        $this->assertTrue($cloudflare->delete('img'));
        $request = $this->lastRequest();
        $this->assertSame('https://cf.test/cfaccount123/images/img', (string)$request->getUri());
        $this->assertSame('Bearer override-token', $request->getHeaderLine('Authorization'));
    }

    public function testUploadUrlSuccess(): void
    {
        $cloudflare = $this->cloudflare([new Response(200, [], '{"success":true,"result":{"id":"17"}}')]);

        $this->assertTrue($cloudflare->uploadUrl('https://example.com/image.jpg', '17'));
        $request = $this->lastRequest();
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame(
            'https://api.cloudflare.com/client/v4/accounts/cfaccount123/images/v1',
            (string)$request->getUri(),
        );
        $this->assertSame('Bearer cftoken123', $request->getHeaderLine('Authorization'));
        $body = (string)$request->getBody();
        $this->assertStringContainsString('https://example.com/image.jpg', $body);
        $this->assertStringContainsString('name="id"', $body);
    }

    public function testUploadUrlUnsuccessfulBody(): void
    {
        $cloudflare = $this->cloudflare([new Response(200, [], '{"success":false}')]);

        $this->assertFalse($cloudflare->uploadUrl('https://example.com/image.jpg', '17'));
    }

    public function testUploadUrlInvalidJson(): void
    {
        $cloudflare = $this->cloudflare([new Response(200, [], 'not json')]);

        $this->assertFalse($cloudflare->uploadUrl('https://example.com/image.jpg', '17'));
    }

    public function testUploadUrlExistingImageIsSuccess(): void
    {
        $cloudflare = $this->cloudflare([new Response(409, [], '{"success":false}')]);

        $this->assertTrue($cloudflare->uploadUrl('https://example.com/image.jpg', '17'));
    }

    public function testUploadUrlServerError(): void
    {
        $cloudflare = $this->cloudflare([new Response(500)]);

        $this->assertFalse($cloudflare->uploadUrl('https://example.com/image.jpg', '17'));
    }

    public function testUploadUrlConnectionError(): void
    {
        $cloudflare = $this->cloudflare([new ConnectException('down', new Request('POST', 'test'))]);

        $this->assertFalse($cloudflare->uploadUrl('https://example.com/image.jpg', '17'));
    }

    public function testDelete(): void
    {
        $cloudflare = $this->cloudflare([new Response(200, [], '{"success":true}')]);

        $this->assertTrue($cloudflare->delete('17'));
        $request = $this->lastRequest();
        $this->assertSame('DELETE', $request->getMethod());
        $this->assertStringEndsWith('/images/v1/17', (string)$request->getUri());
    }

    public function testDeleteNotFound(): void
    {
        $cloudflare = $this->cloudflare([new Response(404)]);

        $this->assertFalse($cloudflare->delete('17'));
    }

    public function testGet(): void
    {
        $cloudflare = $this->cloudflare([new Response(200, [], '{"success":true,"result":{"id":"17"}}')]);

        $this->assertSame('{"success":true,"result":{"id":"17"}}', $cloudflare->get('17'));
        $this->assertSame('GET', $this->lastRequest()->getMethod());
    }

    public function testGetFailure(): void
    {
        $cloudflare = $this->cloudflare([new Response(404)]);

        $this->assertNull($cloudflare->get('17'));
    }
}
