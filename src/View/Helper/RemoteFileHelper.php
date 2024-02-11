<?php
declare(strict_types=1);

namespace RemoteFiles\View\Helper;

use Cake\View\Helper;
use RemoteFiles\Lib\RemotePathTrait;

/**
 * Class: RemoteFileHelper
 *
 * @see Helper
 */
class RemoteFileHelper extends Helper
{
    use RemotePathTrait;

    /**
     * Additional helper to load
     *
     * @var array
     */
    public array $helpers = ['Html'];

    /**
     * Creates an HTML image sourced from a remote file on Cloudflare Images
     *
     * @param string $imageId The image name/id
     * @param array $options The options
     * @return string An HTML image string or an empty string
     */
    public function image(string $imageId, array $options = []): string
    {
        $image = '';
        $defaultClass = 'img-fluid';

        if ($imageId) {
            $options['class'] = empty($options['class']) ? $defaultClass : $options['class'];
            $path = $this->getRemoteImageUrl($imageId, $options);
            unset($options['variant']);
            $image = $this->Html->image($path, $options);
        }

        return $image;
    }

    /**
     * Gets a path to a remote file
     *
     * @param string $name The remote file to get
     * @param string $extension The optional file extension
     * @return string A path to a remote file or empty string
     */
    public function file(string $name, string $extension): string
    {
        $path = '';

        if ($name && $extension) {
            $path = $this->getRemoteFileUrl($name, $extension);
        }

        return $path;
    }
}
