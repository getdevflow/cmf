<?php

declare(strict_types=1);

use Codefy\Framework\Migration\Migration;
use Qubus\Dbal\Schema\AlterTable;
use Qubus\Exception\Exception;

use function Codefy\Framework\Helpers\config;

class AlterContentTable extends Migration
{
    /**
     * Do the migration
     * @throws Exception
     */
    public function up(): void
    {
        $tablePrefix = config('database.connections.default.prefix');

        if ($this->schema()->hasTable(table: $tablePrefix . 'content')) {
            $this->schema()
                ->alter(table: $tablePrefix . 'content', callback: function (AlterTable $table) use ($tablePrefix) {
                    $table->foreign('content_author', $tablePrefix . 'contentAuthor')
                            ->references($tablePrefix . 'user', 'user_id')
                            ->onDelete('set null')
                            ->onUpdate('cascade');
                    $table->foreign('content_type', $tablePrefix . 'contentTypeSlug')
                            ->references($tablePrefix . 'content_type', 'content_type_slug')
                            ->onDelete('cascade')
                            ->onUpdate('cascade');
                    $table->foreign('content_parent', $tablePrefix . 'contentParent')
                            ->references($tablePrefix . 'content', 'content_id')
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

        if ($this->schema()->hasTable(table: $tablePrefix . 'content')) {
            $this->schema()
                ->alter(table: $tablePrefix . 'content', callback: function (AlterTable $table) use ($tablePrefix) {
                    $table->dropForeign(name: $tablePrefix . 'contentAuthor');
                    $table->dropForeign(name: $tablePrefix . 'contentTypeSlug');
                    $table->dropForeign(name: $tablePrefix . 'contentParent');
                });
        }
    }
}
