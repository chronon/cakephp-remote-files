<?php
declare(strict_types=1);

namespace RemoteFiles\Test\TestCase\View\Helper;

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
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $view = new View();
        $this->RemoteFileHelper = new RemoteFileHelper($view);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->RemoteFileHelper);

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
