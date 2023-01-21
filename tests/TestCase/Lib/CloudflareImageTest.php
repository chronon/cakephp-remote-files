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
     * Test configuration
     *
     * @var array
     */
    public $testConfig = [
        'RemoteFiles' => [
            'RemoteStorage' => 'S3',
            'S3' => [
                'managerClass' => 'S3Manager',
                'deliveryBase' => 's3.amazonaws.com',
                'prefix' => 'files/test',
                'clientConfig' => [
                    'version' => 'latest',
                    'region' => 'us-east-2',
                    'bucket' => 'test-bucket',
                    'credentials' => [
                        'key'    => 's3key123',
                        'secret' => 's3secret123',
                    ],
                ],

            ],
            'Cloudflare' => [
                'Images' => [
                    'apiUrl' => 'https://api.cloudflare.com/client/v4/accounts/%s/images/v1',
                    'delivery' => [
                        'url' => 'https://imagedelivery.net',
                        'hash' => 'hash123',
                    ],
                    'Auth' => [
                        'account' => 'cfaccount123',
                        'token' => 'cftoken123',
                    ],
                ],
            ],
        ],
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        Configure::write($this->testConfig);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        Configure::write('RemoteFiles', []);
        parent::tearDown();
    }

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
