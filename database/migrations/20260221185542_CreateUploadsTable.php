<?php

declare(strict_types=1);

use Qubus\Exception\Exception;
use Qubus\Expressive\Migration\Migration;
use Qubus\Expressive\Schema\CreateTable;

use function Codefy\Framework\Helpers\config;

class CreateUploadsTable extends Migration
{
    /**
     * Do the migration
     * @throws Exception
     */
    public function up(): void
    {
        $default = config()->string(key: 'database.default');
        $tablePrefix = config()->string(key: "database.connections.{$default}.prefix");

        if (!$this->schema()->hasTable(table: $tablePrefix . 'uploads')) {
            $this->schema()
                ->create(table: $tablePrefix . 'uploads', callback: function (CreateTable $table) {
                    $table->integer(name: 'id')->notNull()->autoIncrement();
                    $table->string(name: 'public_id', length: 50)->notNull()->unique();
                    $table->string(name: 'original_file', length: 512)->notNull();
                    $table->string(name: 'mime_type', length: 50)->notNull();
                    $table->string(name: 'server_file', length: 512)->notNull()->unique();
                });
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

        if ($this->schema()->hasTable(table: $tablePrefix . 'uploads')) {
            $this->schema()->drop(table: $tablePrefix . 'uploads');
        }
    }
}
