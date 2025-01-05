<?php

declare(strict_types=1);

use App\Application\Devflow;
use Codefy\Framework\Migration\Migration;
use Qubus\Exception\Exception;

use function Codefy\Framework\Helpers\config;

class CreateElfinderFilePdoTable extends Migration
{
    /**
     * Do the migration
     * @throws Exception
     */
    public function up(): void
    {
        $tablePrefix = config('database.connections.default.prefix');

        if (!$this->schema()->hasTable(table: $tablePrefix . 'elfinder_file')) {
            $sql = "CREATE TABLE `{$tablePrefix}elfinder_file` (
                    `id` int(7) UNSIGNED NOT NULL,
                    `parent_id` int(7) UNSIGNED NOT NULL,
                    `name` varchar(255) NOT NULL,
                    `content` longblob NOT NULL,
                    `size` int(10) UNSIGNED NOT NULL DEFAULT 0,
                    `mtime` int(10) UNSIGNED NOT NULL DEFAULT 0,
                    `mime` varchar(256) NOT NULL DEFAULT 'unknown',
                    `read` enum('1','0') NOT NULL DEFAULT '1',
                    `write` enum('1','0') NOT NULL DEFAULT '1',
                    `locked` enum('1','0') NOT NULL DEFAULT '0',
                    `hidden` enum('1','0') NOT NULL DEFAULT '0',
                    `width` int(5) NOT NULL DEFAULT 0,
                    `height` int(5) NOT NULL DEFAULT 0
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                    
                    INSERT INTO `{$tablePrefix}elfinder_file` VALUES(1, 0, 'DATABASE', '', 0, 0, 'directory', '1', '1', '0', '0', 0, 0);";

            Devflow::inst()::$APP->getDB()->getConnection()->getPdo()->exec($sql);
        }
    }

    /**
     * Undo the migration
     * @throws Exception
     */
    public function down(): void
    {
        $tablePrefix = config('database.connections.default.prefix');

        if ($this->schema()->hasTable(table: $tablePrefix . 'elfinder_file')) {
            $this->schema()->drop(table: $tablePrefix . 'elfinder_file');
        }
    }
}
