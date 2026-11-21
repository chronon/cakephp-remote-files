<?php
declare(strict_types=1);

namespace RemoteFiles\Model\Behavior;

use ArrayObject;
use Cake\Core\Configure;
use Cake\Core\Exception\CakeException;
use Cake\Database\TypeFactory;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use Cake\Utility\Text;
use Psr\Http\Message\UploadedFileInterface;
use RemoteFiles\Database\Type\FileType;
use RemoteFiles\Lib\CloudflareImage;
use RemoteFiles\Lib\ManagerFactory;
use RemoteFiles\Lib\RemoteManagerInterface;

/**
 * Uploads files to remote storage on save and removes them on delete.
 *
 * Each config key other than `defaults` is an upload field, with settings merged over `defaults`.
 */
class UploadBehavior extends Behavior
{
    /**
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

    protected RemoteManagerInterface $manager;

    /**
     * @param array<string, mixed> $config The behavior config
     * @return void
     */
    public function initialize(array $config): void
    {
        TypeFactory::map('remote.file', FileType::class);
        $schema = $this->_table->getSchema();
        foreach (array_keys($this->fieldSettings()) as $field) {
            if ($schema->hasColumn($field)) {
                $schema->setColumnType($field, 'remote.file');
            }
        }
        $this->_table->setSchema($schema);

        $this->manager = ManagerFactory::create();
    }

    /**
     * Drop empty uploads for fields that allow empty values, so the existing value is kept.
     *
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event The event
     * @param \ArrayObject<string, mixed> $data The request data
     * @param \ArrayObject<string, mixed> $options The marshal options
     * @return void
     */
    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
        foreach (array_keys($this->fieldSettings()) as $field) {
            $upload = $data[$field] ?? null;
            if (
                $upload instanceof UploadedFileInterface &&
                $upload->getError() === UPLOAD_ERR_NO_FILE &&
                $this->_table->getValidator()->isEmptyAllowed($field, false)
            ) {
                unset($data[$field]);
            }
        }
    }

    /**
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event The event
     * @param \Cake\Datasource\EntityInterface $entity The entity being saved
     * @param \ArrayObject<string, mixed> $options The save options
     * @return void
     * @throws \Cake\Core\Exception\CakeException When an upload failed or could not be written
     */
    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        foreach ($this->fieldSettings() as $field => $settings) {
            $file = $entity->get($field);
            if (!$file instanceof UploadedFileInterface) {
                continue;
            }
            if ($file->getError() !== UPLOAD_ERR_OK) {
                throw new CakeException("There was an error uploading `{$field}`");
            }
            $this->upload($field, $settings, $entity, $file);
        }
    }

    /**
     * Remove the remote files of a deleted entity.
     *
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event The event
     * @param \Cake\Datasource\EntityInterface $entity The deleted entity
     * @param \ArrayObject<string, mixed> $options The delete options
     * @return void
     */
    public function afterDelete(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        foreach ($this->fieldSettings() as $settings) {
            $remote = $entity->get($settings['remoteField']);
            if ($settings['deleteEnabled'] !== true || !is_string($remote) || $remote === '') {
                continue;
            }
            $this->manager->remoteDelete($remote . '.' . $entity->get($settings['extField']));
            if ($settings['cloudflareImage']) {
                $this->cloudflare()->delete($remote);
            }
        }
    }

    /**
     * @param string $field The upload field
     * @param array<string, mixed> $settings The field settings
     * @param \Cake\Datasource\EntityInterface $entity The entity being saved
     * @param \Psr\Http\Message\UploadedFileInterface $file The uploaded file
     * @return void
     * @throws \Cake\Core\Exception\CakeException When the file could not be written
     */
    protected function upload(
        string $field,
        array $settings,
        EntityInterface $entity,
        UploadedFileInterface $file,
    ): void {
        $prefix = $settings['prefix'] ?? null ?: $this->_table->getTable();
        $clientName = (string)$file->getClientFilename();
        $extension = pathinfo($clientName, PATHINFO_EXTENSION);
        $remote = Configure::read('RemoteFiles.globalPrefix', '') . $prefix . '-' . Text::uuid();
        $fileName = "{$remote}.{$extension}";

        $stream = $file->getStream();
        if ($stream->isSeekable()) {
            $stream->rewind();
        }
        if (!$this->manager->remoteWrite($fileName, $stream->getContents())) {
            throw new CakeException("There was an error remote writing `{$fileName}`");
        }
        $entity->set($field, $clientName);
        $entity->set($settings['remoteField'], $remote);
        $entity->set($settings['extField'], $extension);

        if ($settings['cloudflareImage']) {
            $this->cloudflare()->uploadUrl($this->manager->getRemoteUrl($fileName), $remote);
        }
    }

    /**
     * @return \RemoteFiles\Lib\CloudflareImage
     */
    protected function cloudflare(): CloudflareImage
    {
        return new CloudflareImage();
    }

    /**
     * Upload field settings merged over `defaults`. Non-array config values are behavior options, not fields.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function fieldSettings(): array
    {
        $config = $this->getConfig();
        $defaults = $config['defaults'];
        unset($config['defaults']);

        return array_map(fn(array $settings): array => $settings + $defaults, array_filter($config, 'is_array'));
    }
}
