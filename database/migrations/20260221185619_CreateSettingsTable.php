<?php

declare(strict_types=1);

use Qubus\Exception\Exception;
use Qubus\Expressive\Migration\Migration;
use Qubus\Expressive\Schema\CreateTable;

use function Codefy\Framework\Helpers\config;

class CreateSettingsTable extends Migration
{
    /**
     * Do the migration
     * @throws Exception
     */
    public function up(): void
    {
        $default = config()->string(key: 'database.default');
        $tablePrefix = config()->string(key: "database.connections.{$default}.prefix");

        if (!$this->schema()->hasTable(table: $tablePrefix . 'settings')) {
            $this->schema()
                ->create(table: $tablePrefix . 'settings', callback: function (CreateTable $table) {
                    $table->integer(name: 'id')->notNull()->autoIncrement();
                    $table->string(name: 'setting', length: 50)->notNull()->unique();
                    $table->text(name: 'value')->size(value: 'medium')->notNull();
                    $table->integer(name: 'is_array')->notNull();
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

        if ($this->schema()->hasTable(table: $tablePrefix . 'settings')) {
            $this->schema()->drop(table: $tablePrefix . 'settings');
        }
    }
}
