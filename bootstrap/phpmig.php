<?php

declare(strict_types=1);

use Codefy\Framework\Migration\Adapter\DbalMigrationAdapter;
use Qubus\Dbal\DB;
use Qubus\Support\Container\ObjectStorageMap;

use function Codefy\Framework\Helpers\config;
use function Codefy\Framework\Helpers\database_path;
use function Codefy\Framework\Helpers\env;

require __DIR__ . '/../vendor/autoload.php';

$connection = env(key: 'DB_CONNECTION', default: 'default');

$objectmap = new ObjectStorageMap();

$objectmap['db'] = fn () => DB::connection(config: (array) config(key: "database.connections.{$connection}"));

$objectmap['phpmig.adapter'] = function ($c) {
    return new DbalMigrationAdapter(connection: $c['db'], tableName: 'migration');
};

$objectmap['phpmig.migrations_path'] = database_path(path: 'migrations');

return $objectmap;
