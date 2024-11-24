<?php

declare(strict_types=1);

use Codefy\Framework\Migration\Migration;
use Qubus\Dbal\Schema\AlterTable;
use Qubus\Exception\Exception;

use function Codefy\Framework\Helpers\config;

class AlterContentmetaTable extends Migration
{
    /**
     * Do the migration
     * @throws Exception
     */
    public function up(): void
    {
        $tablePrefix = config('database.connections.default.prefix');

        if ($this->schema()->hasTable(table: $tablePrefix . 'contentmeta')) {
            $this->schema()
                ->alter(table: $tablePrefix . 'contentmeta', callback: function (AlterTable $table) use ($tablePrefix) {
                    $table->foreign(columns: 'content_id', name: $tablePrefix . 'contentIdMeta')
                        ->references($tablePrefix . 'content', 'content_id')
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

        if ($this->schema()->hasTable(table: $tablePrefix . 'contentmeta')) {
            $this->schema()
                ->alter(table: $tablePrefix . 'contentmeta', callback: function (AlterTable $table) use ($tablePrefix) {
                    $table->dropForeign(name: $tablePrefix . 'contentIdMeta');
                });
        }
    }
}
