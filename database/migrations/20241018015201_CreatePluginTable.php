<?php

declare(strict_types=1);

use Qubus\Exception\Exception;
use Qubus\Expressive\Migration\Migration;
use Qubus\Expressive\Schema\CreateTable;

use function Codefy\Framework\Helpers\config;

class CreatePluginTable extends Migration
{
    /**
     * Do the migration
     * @throws Exception
     */
    public function up(): void
    {
        $default = config()->string(key: 'database.default');
        $tablePrefix = config()->string(key: "database.connections.{$default}.prefix");

        if (!$this->schema()->hasTable(table: $tablePrefix . 'plugin')) {
            $this->schema()
                ->create(
                    table: $tablePrefix . 'plugin',
                    callback: function (CreateTable $table) use ($tablePrefix) {
                        $table->string(name: 'plugin_id', length: 36)
                            ->primary(name: 'pluginId')
                            ->unique(name: $tablePrefix . 'pluginId');
                        $table->string(name: 'plugin_classname', length: 191);
                        $table->unique('plugin_classname', $tablePrefix . 'pluginIndex');
                    }
                );
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

        if ($this->schema()->hasTable(table: $tablePrefix . 'plugin')) {
            $this->schema()->drop(table: $tablePrefix . 'plugin');
        }
    }
}
