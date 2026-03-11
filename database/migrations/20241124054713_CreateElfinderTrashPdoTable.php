<?php

declare(strict_types=1);

use App\Application\Devflow;
use Qubus\Exception\Exception;
use Qubus\Expressive\Migration\Migration;

use function Codefy\Framework\Helpers\config;

class CreateElfinderTrashPdoTable extends Migration
{
    /**
     * Do the migration
     * @throws Exception
     */
    public function up(): void
    {
        $tablePrefix = config('database.connections.default.prefix');

        if (!$this->schema()->hasTable(table: $tablePrefix . 'elfinder_trash')) {
            $sql = "CREATE TABLE `{$tablePrefix}elfinder_trash` (
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
                    );
                    
                    INSERT INTO `{$tablePrefix}elfinder_trash` VALUES(1, 0, 'DB Trash', '', 0, 0, 'directory', '1', '1', '0', '0', 0, 0);";

            Devflow::$PHP->getDB()->getConnection()->pdo->exec($sql);
        }
    }

    /**
     * Undo the migration
     * @throws Exception
     */
    public function down(): void
    {
        $tablePrefix = config('database.connections.default.prefix');

        if ($this->schema()->hasTable(table: $tablePrefix . 'elfinder_trash')) {
            $this->schema()->drop(table: $tablePrefix . 'elfinder_trash');
        }
    }
}
