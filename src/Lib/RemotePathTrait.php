<?php
declare(strict_types=1);

namespace RemoteFiles\Lib;

use Cake\Core\Configure;

/**
 * Class: RemotePathTrait
 *
 * @package RemoteFiles\Lib
 */
trait RemotePathTrait
{
    /**
     * Gets the configured Manager instance
     *
     * @return \RemoteFiles\Lib\RemoteManagerInterface
     */
    public function getManager(): RemoteManagerInterface
    {
        $remoteStorage = Configure::read('RemoteFiles.RemoteStorage');
        $managerClass = Configure::read("RemoteFiles.{$remoteStorage}.managerClass");
        $Manager = "RemoteFiles\\Lib\\{$managerClass}";

        /** @phpstan-ignore-next-line */
        return new $Manager();
    }

    /**
     * Gets a path to a remote file
     *
     * @param string $name The remote file to get
     * @param string $extension The optional file extension
     * @return string A path to a remote file or empty string
     */
    public function getRemoteFileUrl(string $name, string $extension = ''): string
    {
        $url = '';

        if ($name) {
            $Manager = $this->getManager();
            $url = $Manager->getRemoteUrl($name);
        }
        if ($name && $extension) {
            $url .= '.' . $extension;
        }

        return $url;
    }

    /**
     * Gets the URL from a remote file on Cloudflare Images
     *
     * @param string $imageId The image name/id
     * @return string The remote image URL or empty string
     */
    public function getRemoteImageUrl(string $imageId): string
    {
        $url = '';

        if ($imageId) {
            $RemoteConfig = Configure::read('RemoteFiles.Cloudflare.Images');
            if ($RemoteConfig) {
                $url = $RemoteConfig['delivery']['url'] . '/';
                $url .= $RemoteConfig['delivery']['hash'] . '/';
                $url .= $imageId . '/';
                $url .= $RemoteConfig['delivery']['variant'] ?? 'default';
            }
        }

        return $url;
    }
}
