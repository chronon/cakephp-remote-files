<?php
declare(strict_types=1);

namespace RemoteFiles\Lib;

use Aws\S3\S3Client;
use Cake\Core\Configure;
use Cake\Log\Log;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;

class S3Manager implements RemoteManagerInterface
{
    /**
     * @inheritDoc
     */
    public function remoteWrite(string $path, string $contents): bool
    {
        try {
            $this->filesystem()->write($path, $contents);
        } catch (FilesystemException $exception) {
            Log::error($exception->getMessage());

            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function remoteDelete(string $remote): bool
    {
        try {
            $this->filesystem()->delete($remote);
        } catch (FilesystemException $exception) {
            Log::error($exception->getMessage());

            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function getRemoteUrl(string $remote): string
    {
        $host = Configure::read('RemoteFiles.S3.clientConfig.bucket') . '.'
            . Configure::read('RemoteFiles.S3.deliveryBase', 's3.amazonaws.com');
        $path = array_filter([Configure::read('RemoteFiles.S3.prefix'), $remote], fn($part) => (string)$part !== '');

        return "https://{$host}/" . implode('/', $path);
    }

    /**
     * Create the AWS S3 client.
     *
     * @return \Aws\S3\S3Client
     */
    public function clientFactory(): S3Client
    {
        return new S3Client(Configure::read('RemoteFiles.S3.clientConfig'));
    }

    /**
     * Create the Flysystem S3 adapter.
     *
     * @return \League\Flysystem\AwsS3V3\AwsS3V3Adapter
     */
    public function adapterFactory(): AwsS3V3Adapter
    {
        return new AwsS3V3Adapter(
            $this->clientFactory(),
            Configure::read('RemoteFiles.S3.clientConfig.bucket'),
            (string)Configure::read('RemoteFiles.S3.prefix'),
        );
    }

    /**
     * @return \League\Flysystem\Filesystem
     */
    protected function filesystem(): Filesystem
    {
        return new Filesystem($this->adapterFactory());
    }
}
