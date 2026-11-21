<?php
declare(strict_types=1);

namespace RemoteFiles\Test\TestApp\Lib;

use RemoteFiles\Lib\RemoteManagerInterface;

class RecordingManager implements RemoteManagerInterface
{
    /**
     * @var array<string, string>
     */
    public static array $writes = [];

    /**
     * @var list<string>
     */
    public static array $deletes = [];

    public static bool $fail = false;

    public static function reset(): void
    {
        static::$writes = [];
        static::$deletes = [];
        static::$fail = false;
    }

    public function remoteWrite(string $path, string $contents): bool
    {
        static::$writes[$path] = $contents;

        return !static::$fail;
    }

    public function remoteDelete(string $remote): bool
    {
        static::$deletes[] = $remote;

        return !static::$fail;
    }

    public function getRemoteUrl(string $remote): string
    {
        return "https://remote.test/{$remote}";
    }
}
