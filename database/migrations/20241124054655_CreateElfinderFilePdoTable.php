<?php

declare(strict_types=1);

use App\Application\Devflow;
use Qubus\Exception\Exception;
use Qubus\Expressive\Database;
use Qubus\Exception\Data\TypeException;
use Qubus\Expressive\Migration\Migration;
use Qubus\Expressive\Schema\CreateTable;

use function Codefy\Framework\Helpers\config;

class CreateElfinderFilePdoTable extends Migration
{
    /**
     * Do the migration
     * @throws TypeException
     * @throws Exception
     */
    public function up(): void
    {
        $default = config()->string(key: 'database.default');
        $tablePrefix = config()->string(key: "database.connections.{$default}.prefix");

        if (!$this->schema()->hasTable(table: $tablePrefix . 'elfinder_file')) {
            $this->schema()
                ->create(table: $tablePrefix . 'elfinder_file', callback: function (CreateTable $table) use ($tablePrefix) {
                    $table->integer(name: 'id')->size(value: 'normal')->unsigned()->notNull();
                    $table->integer(name: 'parent_id')->size(value: 'normal')->unsigned()->notNull();
                    $table->string(name: 'name')->notNull();
                    $table->binary(name: 'content')->size(value: 'big')->notNull();
                    $table->integer(name: 'size')->size(value: 'normal')->unsigned()->notNull()->defaultValue(value: 0);
                    $table->integer(name: 'mtime')->size(value: 'normal')->unsigned()->notNull()->defaultValue(value: 0);
                    $table->string(name: 'mime', length: 256)->notNull()->defaultValue(value: 'unknown');
                    $table->string(name: 'read')->size(value: 'tiny')->notNull()->defaultValue(value: '1');
                    $table->string(name: 'write')->size(value: 'tiny')->notNull()->defaultValue(value: '1');
                    $table->string(name: 'locked')->size(value: 'tiny')->notNull()->defaultValue(value: '0');
                    $table->string(name: 'hidden')->size(value: 'tiny')->notNull()->defaultValue(value: '0');
                    $table->integer(name: 'width')->size(value: 'normal')->notNull()->defaultValue(value: 0);
                    $table->integer(name: 'height')->size(value: 'normal')->notNull()->defaultValue(value: 0);
                });

            $sql = "INSERT INTO `{$tablePrefix}elfinder_file` VALUES(1, 0, 'DATABASE', '', 0, 0, 'directory', '1', '1', '0', '0', 0, 0);";
            /** @var Database $dfdb */
            $dfdb = Devflow::$PHP->make(name: Database::class);
            $dfdb->getConnection()->pdo->exec($sql);
        }
    }

    /**
     * Undo the migration
     * @throws Exception
     */
    public function down(): void
    {
        $default = config()->string(key: 'database.default');
        $tablePrefix = config()->string(key: "database.connections.{$default}.prefix");

        if ($this->schema()->hasTable(table: $tablePrefix . 'elfinder_file')) {
            $this->schema()->drop(table: $tablePrefix . 'elfinder_file');
        }
    }
}
