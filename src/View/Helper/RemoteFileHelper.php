<?php
declare(strict_types=1);

namespace RemoteFiles\View\Helper;

use Cake\View\Helper;
use RemoteFiles\Lib\RemotePathTrait;

/**
 * @property \Cake\View\Helper\HtmlHelper $Html
 * @extends \Cake\View\Helper<\Cake\View\View>
 */
class RemoteFileHelper extends Helper
{
    use RemotePathTrait;

    protected array $helpers = ['Html'];

    /**
     * Create an image tag for a Cloudflare Images image.
     *
     * @param string|null $imageId The image ID
     * @param array<string, mixed> $options HtmlHelper::image() options plus `variant`; `class` defaults to `img-fluid`
     * @return string The image tag, or an empty string when `$imageId` is empty
     */
    public function image(?string $imageId, array $options = []): string
    {
        if ($imageId === null || $imageId === '') {
            return '';
        }

        if (empty($options['class'])) {
            $options['class'] = 'img-fluid';
        }
        $path = $this->getRemoteImageUrl($imageId, $options);
        unset($options['variant']);

        return $this->Html->image($path, $options);
    }

    /**
     * Get the URL of a remote file.
     *
     * @param string|null $name The remote file name
     * @param string|null $extension The file extension
     * @return string The URL, or an empty string when either argument is empty
     */
    public function file(?string $name, ?string $extension): string
    {
        if ($name === null || $name === '' || $extension === null || $extension === '') {
            return '';
        }

        return $this->getRemoteFileUrl($name, $extension);
    }
}
