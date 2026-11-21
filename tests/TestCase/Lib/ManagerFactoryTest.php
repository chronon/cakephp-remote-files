<?php
declare(strict_types=1);

namespace RemoteFiles\Test\TestCase\Lib;

use Cake\Core\Configure;
use Cake\Core\Exception\CakeException;
use RemoteFiles\Lib\ManagerFactory;
use RemoteFiles\Lib\S3Manager;
use RemoteFiles\Test\TestApp\Lib\RecordingManager;
use RemoteFiles\Test\TestCase\RemoteFilesTestCase;
use stdClass;

class ManagerFactoryTest extends RemoteFilesTestCase
{
    public function testShortClassName(): void
    {
        $this->assertInstanceOf(S3Manager::class, ManagerFactory::create());
    }

    public function testFullyQualifiedClassName(): void
    {
        Configure::write('RemoteFiles.RemoteStorage', 'Recording');
        Configure::write('RemoteFiles.Recording.managerClass', RecordingManager::class);

        $this->assertInstanceOf(RecordingManager::class, ManagerFactory::create());
    }

    public function testMissingManagerClass(): void
    {
        Configure::write('RemoteFiles.RemoteStorage', 'Nope');

        $this->expectException(CakeException::class);
        ManagerFactory::create();
    }

    public function testClassNotImplementingInterface(): void
    {
        Configure::write('RemoteFiles.S3.managerClass', stdClass::class);

        $this->expectException(CakeException::class);
        ManagerFactory::create();
    }
}
