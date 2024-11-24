<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Persistence\NativePdoDatabase;
use Codefy\Framework\Application;
use Qubus\Exception\Data\TypeException;

use function Codefy\Framework\Helpers\env;

try {
    $app = new Application(
        params: [
            'basePath' => env(key: 'APP_BASE_PATH', default: dirname(path: __DIR__))
        ]
    );

    $default = $app->configContainer->getConfigKey(key: 'database.default');
    try {
        $dsn = sprintf(
            '%s:dbname=%s;host=%s',
            $app->configContainer->getConfigKey(key: "database.connections.{$default}.driver"),
            $app->configContainer->getConfigKey(key: "database.connections.{$default}.dbname"),
            $app->configContainer->getConfigKey(key: "database.connections.{$default}.host")
        );
    } catch (\Qubus\Exception\Exception $e) {
        return $e->getMessage();
    }

    $app->define(name: PDO::class, args: [
        ':dsn' => $dsn,
        ':username' => $app->configContainer->getConfigKey(key: "database.connections.{$default}.username"),
        ':password' => $app->configContainer->getConfigKey(key: "database.connections.{$default}.password"),
        ':options' => [],
    ]);
    $app->alias(Database::class, NativePdoDatabase::class);
    $app->share(nameOrInstance: $app);

    return $app;
} catch (TypeException | \Qubus\Exception\Exception $e) {
    return $e->getMessage();
}
