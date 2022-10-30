<?php
declare(strict_types=1);

namespace RemoteFiles\Lib;

/**
 * Class: RemoteManagerInterface
 *
 * @package RemoteFiles\Lib
 */
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
     * @param string $remote The path to write to
     * @return bool True on successful delete
     */
    public function remoteDelete(string $remote): bool;

    /**
     * Gets the full URL for the supplied $remote key
     *
     * @param string $remote The remote key
     * @return string The full remote URL
     */
    public function getRemoteUrl(string $remote): string;

    /**
     * Client Factory
     *
     * @return mixed
     */
    public function clientFactory();

    /**
     * Adapter Factory
     *
     * @return mixed
     */
    public function adapterFactory();
}
