<?php
declare(strict_types=1);

namespace RemoteFiles\Lib;

use Cake\Core\Configure;
use Cake\Core\Exception\CakeException;

class ManagerFactory
{
    /**
     * Create the manager configured by `RemoteFiles.RemoteStorage`.
     *
     * `managerClass` is either a class name in this namespace or a fully qualified class name.
     *
     * @return \RemoteFiles\Lib\RemoteManagerInterface
     * @throws \Cake\Core\Exception\CakeException When the manager is missing or invalid
     */
    public static function create(): RemoteManagerInterface
    {
        $storage = Configure::read('RemoteFiles.RemoteStorage');
        $class = Configure::read("RemoteFiles.{$storage}.managerClass");
        if (!is_string($class) || $class === '') {
            throw new CakeException("No RemoteFiles managerClass configured for storage `{$storage}`");
        }
        if (!str_contains($class, '\\')) {
            $class = __NAMESPACE__ . '\\' . $class;
        }
        if (!is_subclass_of($class, RemoteManagerInterface::class)) {
            throw new CakeException("`{$class}` must implement " . RemoteManagerInterface::class);
        }

        return new $class();
    }
}
