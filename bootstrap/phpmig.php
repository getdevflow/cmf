<?php

declare(strict_types=1);

use App\Application\Devflow;
use Qubus\Expressive\Migration\Adapter\DbalMigrationAdapter;
use Qubus\Support\Container\ObjectStorageMap;

use function Codefy\Framework\Helpers\database_path;
use function Qubus\Config\Helpers\env;

require __DIR__ . '/../vendor/autoload.php';

$connection = env(key: 'DB_CONNECTION', default: 'default');

$objectmap = new ObjectStorageMap();

$objectmap['db'] = fn () => Devflow::$PHP->getDbConnection();

$objectmap['phpmig.adapter'] = function ($c) {
    return new DbalMigrationAdapter(connection: $c['db'], tableName: 'migration');
};

$objectmap['phpmig.migrations_path'] = database_path(path: 'migrations');

return $objectmap;
