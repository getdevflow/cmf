<?php

declare(strict_types=1);

use Codefy\Framework\Migration\Migration;
use Qubus\Dbal\Schema\CreateTable;
use Qubus\Exception\Exception;

use function Codefy\Framework\Helpers\config;

class CreateUsermetaTable extends Migration
{
    /**
     * Do the migration
     * @throws Exception
     */
    public function up(): void
    {
        $tablePrefix = config('database.connections.default.prefix');

        if (!$this->schema()->hasTable(table: $tablePrefix . 'usermeta')) {
            $this->schema()
                ->create(table: $tablePrefix . 'usermeta', callback: function (CreateTable $table) use ($tablePrefix) {
                    $table->string(name: 'meta_id', length: 36)
                        ->primary(name: 'userMetaId')
                        ->unique(name: $tablePrefix . 'userMetaId');
                    $table->string(name: 'user_id', length: 191);
                    $table->string(name: 'meta_key', length: 191);
                    $table->text(name: 'meta_value')->size(value: 'big');
                    $table->unique(['user_id', 'meta_key'], $tablePrefix . 'userMetaIndex');
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

        if ($this->schema()->hasTable(table: $tablePrefix . 'usermeta')) {
            $this->schema()->drop(table: $tablePrefix . 'usermeta');
        }
    }
}
