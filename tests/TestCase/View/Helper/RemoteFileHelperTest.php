<?php
declare(strict_types=1);

namespace RemoteFiles\Test\TestCase\View\Helper;

use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use RemoteFiles\View\Helper\RemoteFileHelper;

/**
 * RemoteFiles\View\Helper\RemoteFileHelper Test Case
 */
class RemoteFileHelperTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \RemoteFiles\View\Helper\RemoteFileHelper
     */
    public $RemoteFileHelper;

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
        $view = new View();
        $this->RemoteFileHelper = new RemoteFileHelper($view);
        Configure::write($this->testConfig);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->RemoteFileHelper);
        Configure::write('RemoteFiles', []);

        parent::tearDown();
    }

    /**
     * Test image method when path is empty
     *
     * @return void
     */
    public function testImageEmpty(): void
    {
        $path = '';

        $result = $this->RemoteFileHelper->image($path);

        $this->assertSame($path, $result);
    }

    /**
     * Test image method when path is valid
     *
     * @return void
     */
    public function testImageNotNullDefaultClass(): void
    {
        $path = 'baz';
        $options = ['alt' => 'Foo Bar'];
        $expected = 'alt="Foo Bar" class="img-fluid"';

        $result = $this->RemoteFileHelper->image($path, $options);
        $this->assertStringContainsString('imagedelivery.net', $result);
        $this->assertStringContainsString($path, $result);
        $this->assertStringContainsString($expected, $result);
    }

    /**
     * Test image method when path is valid and a class is supplied
     *
     * @return void
     */
    public function testImageNotNullWithClass(): void
    {
        $path = 'foo';
        $options = ['alt' => 'Foo Bar', 'class' => 'lovely'];
        $expected = 'alt="Foo Bar" class="lovely"';

        $result = $this->RemoteFileHelper->image($path, $options);
        $this->assertStringContainsString('imagedelivery.net', $result);
        $this->assertStringContainsString($path, $result);
        $this->assertStringContainsString($expected, $result);
    }
}
