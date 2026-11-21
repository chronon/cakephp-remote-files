<?php
declare(strict_types=1);

namespace RemoteFiles\Database\Type;

use Cake\Database\Driver;
use Cake\Database\Type\BaseType;

/**
 * Passes values through unchanged, so uploaded file objects survive marshalling until the behavior handles them.
 */
class FileType extends BaseType
{
    /**
     * @inheritDoc
     */
    public function marshal(mixed $value): mixed
    {
        return $value;
    }

    /**
     * @inheritDoc
     */
    public function toDatabase(mixed $value, Driver $driver): mixed
    {
        return $value;
    }

    /**
     * @inheritDoc
     */
    public function toPHP(mixed $value, Driver $driver): mixed
    {
        return $value;
    }
}
