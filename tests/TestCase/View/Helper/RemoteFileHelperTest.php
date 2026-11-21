<?php
declare(strict_types=1);

namespace RemoteFiles\Test\TestCase\View\Helper;

use Cake\View\View;
use RemoteFiles\Test\TestCase\RemoteFilesTestCase;
use RemoteFiles\View\Helper\RemoteFileHelper;

class RemoteFileHelperTest extends RemoteFilesTestCase
{
    protected RemoteFileHelper $helper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->helper = new RemoteFileHelper(new View());
    }

    public function testImageEmpty(): void
    {
        $this->assertSame('', $this->helper->image(''));
        $this->assertSame('', $this->helper->image(null));
    }

    public function testImageDefaults(): void
    {
        $this->assertSame(
            '<img src="https://imagedelivery.net/hash123/baz/default" alt="Foo Bar" class="img-fluid">',
            $this->helper->image('baz', ['alt' => 'Foo Bar']),
        );
    }

    public function testImageWithClassAndVariant(): void
    {
        $this->assertSame(
            '<img src="https://imagedelivery.net/hash123/foo/thumb" alt="Foo Bar" class="lovely">',
            $this->helper->image('foo', ['alt' => 'Foo Bar', 'class' => 'lovely', 'variant' => 'thumb']),
        );
    }

    public function testGetRemoteImageUrlEmptyVariant(): void
    {
        $this->assertSame(
            'https://imagedelivery.net/hash123/foo/',
            $this->helper->getRemoteImageUrl('foo', ['variant' => '']),
        );
    }

    public function testFile(): void
    {
        $this->assertSame(
            'https://test-bucket.s3.amazonaws.com/files/test/downloads-abc.pdf',
            $this->helper->file('downloads-abc', 'pdf'),
        );
    }

    public function testFileEmpty(): void
    {
        $this->assertSame('', $this->helper->file('', 'pdf'));
        $this->assertSame('', $this->helper->file('downloads-abc', ''));
        $this->assertSame('', $this->helper->file(null, null));
    }

    public function testGetRemoteFileUrlWithoutExtension(): void
    {
        $this->assertSame(
            'https://test-bucket.s3.amazonaws.com/files/test/downloads-abc',
            $this->helper->getRemoteFileUrl('downloads-abc'),
        );
    }
}
