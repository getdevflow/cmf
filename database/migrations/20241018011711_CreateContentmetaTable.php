<?php

declare(strict_types=1);

use Codefy\Framework\Migration\Migration;
use Qubus\Dbal\Schema\CreateTable;
use Qubus\Exception\Exception;

use function Codefy\Framework\Helpers\config;

class CreateContentmetaTable extends Migration
{
    /**
     * Do the migration
     * @throws Exception
     */
    public function up(): void
    {
        $tablePrefix = config('database.connections.default.prefix');

        if (!$this->schema()->hasTable(table: $tablePrefix . 'contentmeta')) {
            $this->schema()
                ->create(
                    table: $tablePrefix . 'contentmeta',
                    callback: function (CreateTable $table) use ($tablePrefix) {
                        $table->string(name: 'meta_id', length: 36)
                            ->primary(name: 'contentMetaId')
                            ->unique(name: $tablePrefix . 'contentMetaId');
                        $table->string(name: 'content_id', length: 36)->notNull();
                        $table->string(name: 'meta_key', length: 191);
                        $table->text(name: 'meta_value')->size(value: 'big');
                        $table->unique(['content_id', 'meta_key'], $tablePrefix . 'contentMetaIndex');

                        $table->foreign(columns: 'content_id', name: $tablePrefix . 'contentIdMeta')
                                ->references($tablePrefix . 'content', 'content_id')
                                ->onDelete('cascade')
                                ->onUpdate('cascade');
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
        $tablePrefix = config('database.connections.default.prefix');

        if ($this->schema()->hasTable(table: $tablePrefix . 'contentmeta')) {
            $this->schema()->drop(table: $tablePrefix . 'contentmeta');
        }
    }
}
