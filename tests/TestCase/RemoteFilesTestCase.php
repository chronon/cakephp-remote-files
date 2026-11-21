<?php
declare(strict_types=1);

namespace RemoteFiles\Test\TestCase;

use Cake\Core\Configure;
use Cake\TestSuite\TestCase;

abstract class RemoteFilesTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Configure::write('RemoteFiles', [
            'RemoteStorage' => 'S3',
            'S3' => [
                'managerClass' => 'S3Manager',
                'deliveryBase' => 's3.amazonaws.com',
                'prefix' => 'files/test',
                'clientConfig' => [
                    'version' => 'latest',
                    'region' => 'us-east-2',
                    'bucket' => 'test-bucket',
                    'credentials' => [
                        'key' => 's3key123',
                        'secret' => 's3secret123',
                    ],
                ],
            ],
            'Cloudflare' => [
                'Images' => [
                    'apiUrl' => 'https://api.cloudflare.com/client/v4/accounts/%s/images/v1',
                    'delivery' => [
                        'url' => 'https://imagedelivery.net',
                        'hash' => 'hash123',
                    ],
                    'Auth' => [
                        'account' => 'cfaccount123',
                        'token' => 'cftoken123',
                    ],
                ],
            ],
        ]);
    }
}
