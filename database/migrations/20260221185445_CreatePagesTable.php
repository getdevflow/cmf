<?php

declare(strict_types=1);

use Qubus\Exception\Exception;
use Qubus\Expressive\Migration\Migration;
use Qubus\Expressive\Schema\CreateTable;

use function Codefy\Framework\Helpers\config;

class CreatePagesTable extends Migration
{
    /**
     * Do the migration
     * @throws Exception
     */
    public function up(): void
    {
        $default = config()->string(key: 'database.default');
        $tablePrefix = config()->string(key: "database.connections.{$default}.prefix");

        if (!$this->schema()->hasTable(table: $tablePrefix . 'pages')) {
            $this->schema()
                ->create(table: $tablePrefix . 'pages', callback: function (CreateTable $table) {
                    $table->integer(name: 'id')->notNull()->autoIncrement();
                    $table->string(name: 'name')->notNull();
                    $table->string(name: 'show_in_nav')->notNull();
                    $table->integer(name: 'nav_position')->notNull();
                    $table->string(name: 'nav_type')->notNull();
                    $table->string(name: 'layout')->notNull();
                    $table->text(name: 'data')->size(value: 'big')->defaultValue(null);
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

        if ($this->schema()->hasTable(table: $tablePrefix . 'pages')) {
            $this->schema()->drop(table: $tablePrefix . 'pages');
        }
    }
}
