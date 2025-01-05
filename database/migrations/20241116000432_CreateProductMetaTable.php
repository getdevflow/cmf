<?php

declare(strict_types=1);

use Codefy\Framework\Migration\Migration;
use Qubus\Dbal\Schema\AlterTable;
use Qubus\Dbal\Schema\CreateTable;
use Qubus\Exception\Exception;

use function Codefy\Framework\Helpers\config;

class CreateProductMetaTable extends Migration
{
    /**
     * Do the migration
     * @throws Exception
     */
    public function up(): void
    {
        $tablePrefix = config('database.connections.default.prefix');

        if (!$this->schema()->hasTable(table: $tablePrefix . 'productmeta')) {
            $this->schema()
                ->create(
                    table: $tablePrefix . 'productmeta',
                    callback: function (CreateTable $table) use ($tablePrefix) {
                            $table->string(name: 'meta_id', length: 36)
                            ->primary(name: 'productMetaId')
                            ->unique(name: $tablePrefix . 'productMetaId');
                            $table->string(name: 'product_id', length: 36)->notNull();
                            $table->string(name: 'meta_key', length: 191);
                            $table->text(name: 'meta_value')->size(value: 'big');
                            $table->unique(['product_id', 'meta_key'], $tablePrefix . 'productMetaIndex');

                            $table->foreign(columns: 'product_id', name: $tablePrefix . 'productIdMeta')
                            ->references($tablePrefix . 'product', 'product_id')
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

        if ($this->schema()->hasTable(table: $tablePrefix . 'productmeta')) {
            $this->schema()
                ->alter(table: $tablePrefix . 'productmeta', callback: function (AlterTable $table) use ($tablePrefix) {
                    $table->dropForeign(name: $tablePrefix . 'productIdMeta');
                });
            $this->schema()->drop(table: $tablePrefix . 'productmeta');
        }
    }
}
