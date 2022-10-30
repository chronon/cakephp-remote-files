<?php
declare(strict_types=1);

namespace RemoteFiles\Lib;

use Aws\S3\S3Client;
use Cake\Core\Configure;
use Cake\Log\Log;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToWriteFile;

/**
 * Class: S3Manager
 *
 * @package RemoteFiles\Lib
 */
class S3Manager implements RemoteManagerInterface
{
    /**
     * Write a file to the remote filesystem.
     *
     * @param string $path The path to write to
     * @param string $contents The contents to write
     * @return bool True on successful write
     * @throws \League\Flysystem\FilesystemException
     * @throws \League\Flysystem\UnableToWriteFile
     */
    public function remoteWrite(string $path, string $contents): bool
    {
        $result = true;
        $adapter = $this->adapterFactory();
        $filesystem = new Filesystem($adapter);
        try {
            $filesystem->write($path, $contents);
        } catch (FilesystemException | UnableToWriteFile $exception) {
            Log::error($exception->getMessage());
            $result = false;
        } finally {
            return $result;
        }
    }

    /**
     * Delete a file from the remote filesystem.
     *
     * @param string $remote The path to write to
     * @return bool True on successful delete
     * @throws \League\Flysystem\FilesystemException
     * @throws \League\Flysystem\UnableToWriteFile
     */
    public function remoteDelete(string $remote): bool
    {
        $result = true;
        $adapter = $this->adapterFactory();
        $filesystem = new Filesystem($adapter);
        try {
            $filesystem->delete($remote);
        } catch (FilesystemException | UnableToWriteFile $exception) {
            Log::error($exception->getMessage());
            $result = false;
        } finally {
            return $result;
        }
    }

    /**
     * Gets the full URL for the supplied $remote key
     *
     * @param string $remote The remote key
     * @return string The full remote URL
     */
    public function getRemoteUrl(string $remote): string
    {
        $url = 'https://';
        $url .= Configure::read('RemoteFiles.S3.clientConfig.bucket');
        $url .= '.s3.amazonaws.com/';
        $url .= Configure::read('RemoteFiles.S3.prefix');
        $url .= "/{$remote}";

        return $url;
    }

    /**
     * AWS S3 Client Factory
     *
     * @return \Aws\S3\S3Client
     */
    public function clientFactory(): S3Client
    {
        return new S3Client(Configure::read('RemoteFiles.S3.clientConfig'));
    }

    /**
     * Flysystem S3 Adapter Factory
     *
     * @return \League\Flysystem\AwsS3V3\AwsS3V3Adapter
     */
    public function adapterFactory(): AwsS3V3Adapter
    {
        return new AwsS3V3Adapter(
            $this->clientFactory(),
            Configure::read('RemoteFiles.S3.clientConfig.bucket'),
            Configure::read('RemoteFiles.S3.prefix')
        );
    }
}
