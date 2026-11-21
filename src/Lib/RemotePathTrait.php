<?php
declare(strict_types=1);

namespace RemoteFiles\Lib;

use Cake\Core\Configure;

trait RemotePathTrait
{
    /**
     * Get the configured remote manager.
     *
     * @return \RemoteFiles\Lib\RemoteManagerInterface
     */
    public function getManager(): RemoteManagerInterface
    {
        return ManagerFactory::create();
    }

    /**
     * Get the URL of a remote file.
     *
     * @param string $name The remote file name
     * @param string $extension Optional file extension
     * @return string The URL, or an empty string when `$name` is empty
     */
    public function getRemoteFileUrl(string $name, string $extension = ''): string
    {
        if ($name === '') {
            return '';
        }

        $url = $this->getManager()->getRemoteUrl($name);

        return $extension === '' ? $url : "{$url}.{$extension}";
    }

    /**
     * Get the Cloudflare Images delivery URL of an image.
     *
     * @param string $imageId The image ID
     * @param array<string, mixed> $options `variant` selects the image variant, defaults to `default`
     * @return string The URL, or an empty string when `$imageId` is empty or delivery is not configured
     */
    public function getRemoteImageUrl(string $imageId, array $options = []): string
    {
        $delivery = Configure::read('RemoteFiles.Cloudflare.Images.delivery');
        if ($imageId === '' || !is_array($delivery)) {
            return '';
        }

        return implode('/', [$delivery['url'], $delivery['hash'], $imageId, $options['variant'] ?? 'default']);
    }
}
