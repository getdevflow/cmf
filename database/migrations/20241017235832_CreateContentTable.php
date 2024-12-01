<?php

declare(strict_types=1);

use Codefy\Framework\Migration\Migration;
use Qubus\Dbal\Schema\CreateTable;
use Qubus\Exception\Exception;

use function Codefy\Framework\Helpers\config;

class CreateContentTable extends Migration
{
    /**
     * Do the migration
     * @throws Exception
     */
    public function up(): void
    {
        $tablePrefix = config('database.connections.default.prefix');

        if (!$this->schema()->hasTable(table: $tablePrefix . 'content_type')) {
            $this->schema()
                ->create(
                    table: $tablePrefix . 'content_type',
                    callback: function (CreateTable $table) use ($tablePrefix) {
                        $table->string(name: 'content_type_id', length: 36)
                                ->primary(name: 'contentTypeId')
                                ->unique(name: $tablePrefix . 'contentTypeId');
                        $table->string(name: 'content_type_title', length: 191);
                        $table->string(name: 'content_type_slug', length: 191);
                        $table->text(name: 'content_type_description')->size(value: 'big');
                        $table->index('content_type_slug', $tablePrefix . 'contentTypeIndex');
                    }
                );
        }

        if (!$this->schema()->hasTable(table: $tablePrefix . 'content')) {
            $this->schema()
                ->create(table: $tablePrefix . 'content', callback: function (CreateTable $table) use ($tablePrefix) {
                    $table->string(name: 'content_id', length: 36)
                        ->primary('contentId')
                        ->unique(name: $tablePrefix . 'contentId');
                    $table->string(name: 'content_title', length: 191)->notNull();
                    $table->string(name: 'content_slug', length: 191)->notNull();
                    $table->text(name: 'content_body')->size(value: 'big');
                    $table->string(name: 'content_author', length: 36);
                    $table->string(name: 'content_type', length: 191)->notNull();
                    $table->string(name: 'content_parent', length: 36);
                    $table->integer(name: 'content_sidebar')->size(value: 'large')->defaultValue(0);
                    $table->integer(name: 'content_show_in_menu')->size(value: 'large')->defaultValue(0);
                    $table->integer(name: 'content_show_in_search')->size(value: 'large')->defaultValue(0);
                    $table->string(name: 'content_featured_image', length: 191);
                    $table->string(name: 'content_status', length: 36)->notNull()->defaultValue('draft');
                    $table->string(name: 'content_created', length: 191);
                    $table->dateTime(name: 'content_created_gmt');
                    $table->string(name: 'content_published', length: 191);
                    $table->dateTime(name: 'content_published_gmt');
                    $table->string(name: 'content_modified', length: 191);
                    $table->dateTime(name: 'content_modified_gmt');
                    $table->index(['content_slug','content_type','content_parent'], $tablePrefix . 'contentIndex');

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
            $this->schema()->drop(table: $tablePrefix . 'content');
        }

        if ($this->schema()->hasTable(table: $tablePrefix . 'content_type')) {
            $this->schema()->drop(table: $tablePrefix . 'content_type');
        }
    }
}
