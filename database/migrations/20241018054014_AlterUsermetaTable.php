<?php

declare(strict_types=1);

use Codefy\Framework\Migration\Migration;
use Qubus\Dbal\Schema\AlterTable;
use Qubus\Exception\Exception;

use function Codefy\Framework\Helpers\config;

class AlterUsermetaTable extends Migration
{
    /**
     * Do the migration
     * @throws Exception
     */
    public function up(): void
    {
        $tablePrefix = config('database.connections.default.prefix');

        if ($this->schema()->hasTable(table: $tablePrefix . 'usermeta')) {
            $this->schema()
                ->alter(table: $tablePrefix . 'usermeta', callback: function (AlterTable $table) use ($tablePrefix) {
                    $table->foreign(columns: 'user_id', name: $tablePrefix . 'userId')
                        ->references($tablePrefix . 'user', 'user_id')
                        ->onDelete('cascade')
                        ->onUpdate('cascade');
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
            $this->schema()
                ->alter(table: $tablePrefix . 'usermeta', callback: function (AlterTable $table) use ($tablePrefix) {
                    $table->dropForeign(name: $tablePrefix . 'userId');
                });
        }
    }
}