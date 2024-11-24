<?php

declare(strict_types=1);

use Codefy\Framework\Migration\Migration;
use Qubus\Dbal\Schema\CreateTable;
use Qubus\Exception\Exception;

use function Codefy\Framework\Helpers\config;
use function Qubus\Config\Helpers\env;

class CreateOptionTable extends Migration
{
    /**
     * Do the migration
     * @throws Exception
     */
    public function up(): void
    {
        $tablePrefix = config('database.connections.default.prefix');

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
        $tablePrefix = config('database.connections.default.prefix');

        if ($this->schema()->hasTable(table: $tablePrefix . 'option')) {
            $this->schema()->drop(table: $tablePrefix . 'option');
        }
    }
}
