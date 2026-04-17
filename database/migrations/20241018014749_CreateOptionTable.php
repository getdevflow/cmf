<?php

declare(strict_types=1);

use Qubus\Exception\Exception;
use Qubus\Expressive\Migration\Migration;
use Qubus\Expressive\Schema\CreateTable;

use function Codefy\Framework\Helpers\config;

class CreateOptionTable extends Migration
{
    /**
     * Do the migration
     * @throws Exception
     */
    public function up(): void
    {
        $default = config()->string(key: 'database.default');
        $tablePrefix = config()->string(key: "database.connections.{$default}.prefix");

        if (!$this->schema()->hasTable(table: $tablePrefix . 'option')) {
            $this->schema()
                ->create(table: $tablePrefix . 'option', callback: function (CreateTable $table) use ($tablePrefix) {
                    $table->string(name: 'option_id', length: 36)
                        ->primary(name: 'optionId')
                        ->unique(name: $tablePrefix . 'optionId');
                    $table->string(name: 'option_key', length: 191);
                    $table->text(name: 'option_value')->size(value: 'big');
                    $table->unique('option_key', $tablePrefix . 'optionIndex');
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

        if ($this->schema()->hasTable(table: $tablePrefix . 'option')) {
            $this->schema()->drop(table: $tablePrefix . 'option');
        }
    }
}
