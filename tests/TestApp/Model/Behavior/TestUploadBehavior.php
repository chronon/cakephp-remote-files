<?php
declare(strict_types=1);

namespace RemoteFiles\Test\TestApp\Model\Behavior;

use RemoteFiles\Lib\CloudflareImage;
use RemoteFiles\Model\Behavior\UploadBehavior;

class TestUploadBehavior extends UploadBehavior
{
    public static ?CloudflareImage $cloudflare = null;

    protected function cloudflare(): CloudflareImage
    {
        return static::$cloudflare ?? parent::cloudflare();
    }
}
