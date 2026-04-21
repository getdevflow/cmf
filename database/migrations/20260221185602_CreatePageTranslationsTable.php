<?php

declare(strict_types=1);

use Qubus\Exception\Exception;
use Qubus\Expressive\Migration\Migration;
use Qubus\Expressive\Schema\CreateTable;

use function Codefy\Framework\Helpers\config;

class CreatePageTranslationsTable extends Migration
{
    /**
     * Do the migration
     * @throws Exception
     */
    public function up(): void
    {
        $default = config()->string(key: 'database.default');
        $tablePrefix = config()->string(key: "database.connections.{$default}.prefix");

        if (!$this->schema()->hasTable(table: $tablePrefix . 'page_translations')) {
            $this->schema()
                ->create(table: $tablePrefix . 'page_translations', callback: function (CreateTable $table) use ($tablePrefix) {
                    $table->integer(name: 'id')->notNull()->autoIncrement();
                    $table->integer(name: 'page_id')->notNull();
                    $table->string(name: 'locale', length: 50)->notNull();
                    $table->string(name: 'title')->notNull();
                    $table->string(name: 'meta_title')->notNull();
                    $table->string(name: 'meta_description')->notNull();
                    $table->string(name: 'route')->notNull();

                    $table->unique(columns: ['page_id', 'locale'], name: $tablePrefix . 'idx_page_translations');
                    $table->foreign(columns: 'page_id')
                        ->references($tablePrefix . 'pages', 'id')
                        ->onUpdate(action: 'cascade')
                        ->onDelete(action: 'cascade');
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

        if ($this->schema()->hasTable(table: $tablePrefix . 'page_translations')) {
            $this->schema()->drop(table: $tablePrefix . 'page_translations');
        }
    }
}
