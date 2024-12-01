<?php

declare(strict_types=1);

use Codefy\Framework\Migration\Migration;
use Qubus\Dbal\Schema\AlterTable;
use Qubus\Dbal\Schema\CreateTable;
use Qubus\Exception\Exception;

use function Codefy\Framework\Helpers\config;

class CreateProductTable extends Migration
{
    /**
     * Do the migration
     * @throws Exception
     */
    public function up(): void
    {
        $tablePrefix = config('database.connections.default.prefix');

        if (!$this->schema()->hasTable(table: $tablePrefix . 'product')) {
            $this->schema()
                ->create(table: $tablePrefix . 'product', callback: function (CreateTable $table) use ($tablePrefix) {
                    $table->string(name: 'product_id', length: 36)
                            ->primary('productId')
                            ->unique(name: $tablePrefix . 'productId');
                    $table->string(name: 'product_title', length: 191)->notNull();
                    $table->string(name: 'product_slug', length: 191)->notNull();
                    $table->text(name: 'product_body')->size(value: 'big');
                    $table->string(name: 'product_author', length: 36);
                    $table->string(name: 'product_sku', length: 191)->notNull();
                    $table->string(name: 'product_price', length: 191)->defaultValue(0.00);
                    $table->string(name: 'product_currency')->defaultValue('USD');
                    $table->string(name: 'product_purchase_url', length: 191);
                    $table->integer(name: 'product_show_in_menu')->size(value: 'large')->defaultValue(0);
                    $table->integer(name: 'product_show_in_search')->size(value: 'large')->defaultValue(0);
                    $table->string(name: 'product_featured_image', length: 191);
                    $table->string(name: 'product_status', length: 36)->notNull()->defaultValue('draft');
                    $table->string(name: 'product_created', length: 191);
                    $table->dateTime(name: 'product_created_gmt');
                    $table->string(name: 'product_published', length: 191);
                    $table->dateTime(name: 'product_published_gmt');
                    $table->string(name: 'product_modified', length: 191);
                    $table->dateTime(name: 'product_modified_gmt');
                    $table->index(['product_slug','product_sku'], $tablePrefix . 'productIndex');

                    $table->foreign('product_author', $tablePrefix . 'productAuthor')
                            ->references($tablePrefix . 'user', 'user_id')
                            ->onDelete('set null')
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

        if ($this->schema()->hasTable(table: $tablePrefix . 'product')) {
            $this->schema()
                ->alter(table: $tablePrefix . 'product', callback: function (AlterTable $table) use ($tablePrefix) {
                    $table->dropForeign(name: $tablePrefix . 'productAuthor');
                });
            $this->schema()->drop(table: $tablePrefix . 'product');
        }
    }
}
