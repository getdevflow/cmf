<?php

declare(strict_types=1);

use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use Qubus\Expressive\Migration\Migration;
use Qubus\Expressive\Schema\CreateTable;

use function Codefy\Framework\Helpers\config;

class CreateSiteMigrationTable extends Migration
{
    /**
     * Do the migration
     *
     * @throws Exception
     * @throws TypeException
     */
    public function up(): void
    {
        $default = config()->string(key: 'database.default');
        $tablePrefix = config()->string(key: "database.connections.{$default}.prefix");

        if (!$this->schema()->hasTable(table: $tablePrefix . 'site_migration')) {
            $this->schema()
                ->create(
                    table: $tablePrefix . 'site_migration',
                    callback: function (CreateTable $table) use ($tablePrefix) {
                        $table->integer(name: 'id')->autoIncrement();
                        $table->string(name: 'site_id', length: 36)->notNull();
                        $table->string(name: 'migration')->notNull();
                        $table->string(name: 'recorded_on')->notNull();
                        $table->unique(columns: ['site_id', 'migration']);

                        $table->foreign(columns: 'site_id')
                        ->references($tablePrefix . 'site', 'site_id')
                        ->onDelete(action: 'cascade')
                        ->onUpdate(action: 'cascade');
                    }
                );
        }
    }

    /**
     * Undo the migration
     *
     * @throws TypeException
     * @throws Exception
     */
    public function down(): void
    {
        $default = config()->string(key: 'database.default');
        $tablePrefix = config()->string(key: "database.connections.{$default}.prefix");

        if ($this->schema()->hasTable(table: $tablePrefix . 'site_migration')) {
            $this->schema()->drop(table: $tablePrefix . 'site_migration');
        }
    }
}
