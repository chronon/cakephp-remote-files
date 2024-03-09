<?php
declare(strict_types=1);

namespace RemoteFiles\Model\Behavior;

use ArrayObject;
use Cake\Core\Configure;
use Cake\Database\TypeFactory;
use Cake\DataSource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use Cake\Utility\Text;
use Exception;
use Psr\Http\Message\UploadedFileInterface;
use RemoteFiles\Lib\CloudflareImage;
use SplFileInfo;

/**
 * Upload behavior
 */
class UploadBehavior extends Behavior
{
    /**
     * _defaultConfig
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'defaults' => [
            'remoteField' => 'remote',
            'extField' => 'extension',
            'cloudflareImage' => false,
            'deleteEnabled' => true,
        ],
    ];

    /**
     * A Remote File manager instance
     *
     * @var mixed
     */
    protected mixed $Manager;

    /**
     * Build the behaviour
     *
     * @param array $config Passed configuration
     * @return void
     */
    public function initialize(array $config): void
    {
        TypeFactory::map('remote.file', '\RemoteFiles\Database\Type\FileType');
        $schema = $this->_table->getSchema();
        foreach (array_keys($this->getConfig()) as $field) {
            if (is_string($field)) {
                $schema->setColumnType($field, 'remote.file');
            }
        }
        $this->_table->setSchema($schema);

        $remoteStorage = Configure::read('RemoteFiles.RemoteStorage');
        $managerClass = Configure::read("RemoteFiles.{$remoteStorage}.managerClass");
        $Manager = "RemoteFiles\\Lib\\{$managerClass}";
        $this->Manager = new $Manager();
    }

    /**
     * beforeMarshal event
     *
     * @param \Cake\Event\EventInterface $event The event
     * @param \ArrayObject $data data
     * @param \ArrayObject $options options
     * @return void
     * @throws \Cake\Core\Exception\CakeException
     */
    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
        $config = $this->getConfig();
        unset($config['defaults'], $config['className']);
        foreach ($config as $field => $settings) {
            if (!isset($data[$field])) {
                continue;
            }

            $upload = $data[$field];
            if (
                $this->_table->getValidator()->isEmptyAllowed($field, false) &&
                $upload instanceof UploadedFileInterface &&
                $upload->getError() === UPLOAD_ERR_NO_FILE
            ) {
                unset($data[$field]);
            }
        }
    }

    /**
     * beforeSave event
     *
     * @param \Cake\Event\EventInterface $event The event
     * @param \Cake\Datasource\EntityInterface $entity The request entity
     * @param \ArrayObject $options The request options
     * @return void
     */
    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        $config = $this->getConfig();
        $defaults = $config['defaults'];
        unset($config['defaults'], $config['className']);
        foreach ($config as $field => $settings) {
            $settings = array_merge($defaults, $settings);
            if ($entity->get($field) instanceof UploadedFileInterface) {
                if ($entity->get($field)->getError() === UPLOAD_ERR_OK) {
                    $this->upload($field, $settings, $entity);
                } else {
                    throw new Exception("There was an error uploading `{$field}`");
                }
            }
        }
    }

    /**
     * Upload a file to a remote location
     *
     * @param string $field The upload field name
     * @param array $settings Array of upload settings for the field
     * @param \Cake\Datasource\EntityInterface $entity The current entity to process
     * @return void
     */
    protected function upload(string $field, array $settings, EntityInterface $entity): void
    {
        $globalPrefix = Configure::check('RemoteFiles.globalPrefix') ? Configure::read('RemoteFiles.globalPrefix') : '';
        $prefix = !empty($settings['prefix']) ? $settings['prefix'] : $this->_table->getTable();
        $file = $entity->get($field);
        $fileInfo = new SplFileInfo($file->getClientFilename());
        $fileNameBase = $globalPrefix . $prefix . '-' . Text::uuid();
        $fileName = $fileNameBase . '.' . $fileInfo->getExtension();
        if (!$this->Manager->remoteWrite($fileName, $file->getStream()->getContents())) {
            throw new Exception("There was an error remote writing `{$fileName}`");
        }
        $entity->set($field, $file->getClientFilename());
        $entity->set($settings['remoteField'], $fileNameBase);
        $entity->set($settings['extField'], $fileInfo->getExtension());

        if (!empty($settings['cloudflareImage'])) {
            (new CloudflareImage())->uploadUrl($this->Manager->getRemoteUrl($fileName), $fileNameBase);
        }
    }

    /**
     * afterDelete method
     *
     * Remove images from records which have been deleted, if they exist
     *
     * @param \Cake\Event\Event $event The passed event
     * @param \Cake\Datasource\EntityInterface $entity The entity
     * @param \ArrayObject $options Array of options
     * @return void
     */
    public function afterDelete(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        $config = $this->getConfig();
        $defaults = $config['defaults'];
        unset($config['defaults'], $config['className']);
        foreach ($config as $settings) {
            $settings = array_merge($defaults, $settings);
            if ($settings['deleteEnabled'] === true) {
                $remote = $entity->get($settings['remoteField']);
                if (!empty($entity) && !empty($remote)) {
                    $this->Manager->remoteDelete($remote . '.' . $entity->get($settings['extField']));
                }
                if (!empty($settings['cloudflareImage'])) {
                    (new CloudflareImage())->delete($remote);
                }
            }
        }
    }
}
