<?php
declare(strict_types=1);

namespace RemoteFiles\Lib;

interface RemoteManagerInterface
{
    /**
     * Write a file to the remote filesystem.
     *
     * @param string $path The path to write to
     * @param string $contents The contents to write
     * @return bool True on successful write
     */
    public function remoteWrite(string $path, string $contents): bool;

    /**
     * Delete a file from the remote filesystem.
     *
     * @param string $remote The path to delete
     * @return bool True on successful delete
     */
    public function remoteDelete(string $remote): bool;

    /**
     * Get the full URL for a remote key.
     *
     * @param string $remote The remote key
     * @return string The full remote URL
     */
    public function getRemoteUrl(string $remote): string;
}
