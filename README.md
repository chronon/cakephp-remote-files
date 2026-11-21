# RemoteFiles plugin for CakePHP

Uploads files to S3 (optionally mirroring images to Cloudflare Images) through an ORM behavior, and builds
their URLs in views.

## Requirements

* PHP 8.5+
* CakePHP 5.4+

## Installation

```
composer require chronon/remote-files
bin/cake plugin load RemoteFiles
```

## Configuration

```php
'RemoteFiles' => [
    'RemoteStorage' => 'S3',
    // Optional, prepended to every uploaded file name.
    'globalPrefix' => '',
    'S3' => [
        // A class in RemoteFiles\Lib, or a fully qualified class implementing RemoteManagerInterface.
        'managerClass' => 'S3Manager',
        'deliveryBase' => 's3.amazonaws.com',
        'prefix' => 'files/example.com',
        // Passed to Aws\S3\S3Client.
        'clientConfig' => [
            'version' => 'latest',
            'region' => env('AWS_S3_REGION'),
            'bucket' => env('AWS_S3_BUCKET'),
            'credentials' => [
                'key' => env('AWS_S3_KEY'),
                'secret' => env('AWS_S3_SECRET'),
            ],
        ],
    ],
    'Cloudflare' => [
        'Images' => [
            'apiUrl' => 'https://api.cloudflare.com/client/v4/accounts/%s/images/v1',
            'delivery' => [
                'url' => 'https://imagedelivery.net',
                'hash' => 'your-account-hash',
            ],
            'Auth' => [
                'account' => env('CLOUDFLARE_IMAGES_ACCOUNT'),
                'token' => env('CLOUDFLARE_IMAGES_TOKEN'),
            ],
        ],
    ],
],
```

## Upload behavior

Each key is an upload field. On save, an uploaded file is written to remote storage, the field is set to the
client file name, and the remote name and extension are stored in the `remoteField` and `extField` columns. On
delete, the remote file is removed.

```php
$this->addBehavior('RemoteFiles.Upload', [
    'image' => [
        'cloudflareImage' => true,
        'prefix' => 'gallery',
    ],
]);
```

| Option            | Default         | Description                                                     |
|-------------------|-----------------|-----------------------------------------------------------------|
| `remoteField`     | `remote`        | Column for the remote file name, without extension              |
| `extField`        | `extension`     | Column for the file extension                                   |
| `prefix`          | the table name  | Prefix of the generated remote file name                        |
| `cloudflareImage` | `false`         | Also upload to, and delete from, Cloudflare Images              |
| `deleteEnabled`   | `true`          | Delete the remote file when the entity is deleted               |

Defaults for every field can be changed with the `defaults` key. An empty upload is dropped before marshalling
when the field's validator allows empty values, so editing a record without choosing a file keeps the existing
one.

## Helper

```php
$this->addHelper('RemoteFiles.RemoteFile');

echo $this->RemoteFile->image($entity->remote, ['alt' => 'Photo', 'variant' => 'thumbnail']);
echo $this->RemoteFile->file($entity->remote, $entity->extension);
```

`image()` uses the Cloudflare Images `default` variant unless `variant` is given, and adds the `img-fluid` class
unless `class` is given. The same URL builders are available outside views through
`RemoteFiles\Lib\RemotePathTrait`.

## Versions

* `1.x`: CakePHP 4
* `2.x`: CakePHP 5, PHP 8.1+
* `3.x`: CakePHP 5.4+, PHP 8.5+
