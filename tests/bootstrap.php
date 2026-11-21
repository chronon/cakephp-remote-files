<?php
declare(strict_types=1);

use Cake\TestSuite\Fixture\SchemaLoader;

$root = dirname(__DIR__);
chdir($root);

require_once $root . '/vendor/autoload.php';
require_once $root . '/vendor/cakephp/cakephp/tests/bootstrap.php';

(new SchemaLoader())->loadSqlFiles('tests/schema.sql', 'test');
