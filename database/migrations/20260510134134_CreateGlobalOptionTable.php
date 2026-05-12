<?php

declare(strict_types=1);

use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use Qubus\Expressive\Migration\Migration;
use Qubus\Expressive\Schema\CreateTable;

use function Codefy\Framework\Helpers\config;

class CreateGlobalOptionTable extends Migration
{
    /**
     * Do the migration
     * @throws Exception
     * @throws TypeException
     */
    public function up(): void
    {
        $default = config()->string(key: 'database.default');
        $tablePrefix = config()->string(key: "database.connections.{$default}.prefix");

        if (!$this->schema()->hasTable(table: $tablePrefix . 'global_option')) {
            $this->schema()
                ->create(table: $tablePrefix . 'global_option', callback: function (CreateTable $table) use ($tablePrefix) {
                    $table->string(name: 'option_key', length: 191)
                        ->primary(name: 'global_option_key')
                        ->notNull();
                    $table->text(name: 'option_value')->size(value: 'big');
                    $table->integer(name: 'autoload')->size(value: 'tiny')->defaultValue(0);
                });
        }
    }

    /**
     * Undo the migration
     * @throws TypeException
     * @throws Exception
     */
    public function down(): void
    {
        $default = config()->string(key: 'database.default');
        $tablePrefix = config()->string(key: "database.connections.{$default}.prefix");

        if ($this->schema()->hasTable(table: $tablePrefix . 'global_option')) {
            $this->schema()->drop(table: $tablePrefix . 'global_option');
        }
    }
}
