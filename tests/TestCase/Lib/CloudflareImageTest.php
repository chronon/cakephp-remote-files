<?php
declare(strict_types=1);

namespace RemoteFiles\Test\TestCase\Lib;

use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use RemoteFiles\Lib\CloudflareImage;

/**
 * Class: CloudflareImageTest
 */
class CloudflareImageTest extends TestCase
{
    /**
     * testConstructorSet
     *
     * @return void
     */
    public function testConstructorSet()
    {
        $CloudflareImage = new CloudflareImage();
        $this->assertInstanceOf('\GuzzleHttp\Client', $CloudflareImage->client);

        $protectedProperties = ['token', 'account', 'apiUrl'];
        foreach ($protectedProperties as $property) {
            $this->assertClassHasAttribute($property, CloudflareImage::class);
            $Reflection = new \ReflectionProperty(CloudflareImage::class, $property);
            $Reflection->setAccessible(true);
            $propertyVal = $Reflection->getValue($CloudflareImage);
            $this->assertNotEmpty($propertyVal);
        }
    }

    /**
     * testConstructorNotSet
     *
     * @return void
     */
    public function testConstructorNotSet()
    {
        Configure::write('RemoteFiles.Cloudflare.Images.Auth.token', null);
        $this->expectException(
            'InvalidArgumentException',
            'Cloudflare Images token, account and apiUrl are required'
        );
        new CloudflareImage();
    }

    /**
     * testClientFactory
     *
     * @return void
     */
    public function testClientFactory()
    {
        $CloudflareImage = new CloudflareImage();
        $client = $CloudflareImage->clientFactory();
        $this->assertInstanceOf('\GuzzleHttp\Client', $client);
    }

    /**
     * testUploadUrlException
     *
     * @return void
     */
    public function testUploadUrlException()
    {
        $url = 'http://example.com/image.jpg';
        $id = '17';
        $exceptionMsg = 'guzzle exception';
        $CloudflareImage = $this->getMockBuilder(CloudflareImage::class)
            ->onlyMethods([])
            ->getMock();
        $CloudflareImage->client = $this->getMockBuilder('\GuzzleHttp\Client')
            ->onlyMethods(['request'])
            ->getMock();
        $CloudflareImage->client->expects($this->once())
            ->method('request')
            ->willThrowException(new \GuzzleHttp\Exception\RequestException($exceptionMsg, new \GuzzleHttp\Psr7\Request('POST', 'test')));

        $result = $CloudflareImage->uploadUrl($url, $id);
        $this->assertFalse($result);
    }

    /**
     * testUploadUrlSuccess
     *
     * @return void
     */
    public function testUploadUrlSuccess()
    {
        $url = 'http://example.com/image.jpg';
        $id = '17';
        $CloudflareImage = $this->getMockBuilder(CloudflareImage::class)
            ->onlyMethods([])
            ->getMock();
        $CloudflareImage->client = $this->getMockBuilder('\GuzzleHttp\Client')
            ->onlyMethods(['request'])
            ->getMock();
        $CloudflareImage->client->expects($this->once())
            ->method('request')
            ->willReturn(new \GuzzleHttp\Psr7\Response(200, [], '{"success":true,"result":{"id":"123","url":"' . $url . '"}}'));

        $result = $CloudflareImage->uploadUrl($url, $id);
        $this->assertTrue($result);
    }

    /**
     * testUploadUrlFailure
     *
     * @return void
     */
    public function testUploadUrlFailure()
    {
        $url = 'http://example.com/image.jpg';
        $id = '17';
        $CloudflareImage = $this->getMockBuilder(CloudflareImage::class)
            ->onlyMethods([])
            ->getMock();
        $CloudflareImage->client = $this->getMockBuilder('\GuzzleHttp\Client')
            ->onlyMethods(['request'])
            ->getMock();
        $CloudflareImage->client->expects($this->once())
            ->method('request')
            ->willReturn(new \GuzzleHttp\Psr7\Response(200, [], '{"success":false}'));

        $result = $CloudflareImage->uploadUrl($url, $id);
        $this->assertFalse($result);
    }
}
