<?php

declare(strict_types=1);

use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use Qubus\Expressive\Database;
use Qubus\Expressive\Schema\CreateTable;

final class CreateSiteContentCommentTable
{
    /**
     * Do the migration
     *
     * @throws Exception
     * @throws TypeException
     */
    public function up(Database $dfdb, string $prefix): void
    {
        if (!$dfdb->schema()->hasTable(table: $prefix . 'content_comment')) {
            $dfdb->schema()->create($prefix . 'content_comment', function (CreateTable $table) use ($dfdb, $prefix) {
                $table->string('comment_id', length: 36)
                    ->primary()
                    ->unique($prefix . 'contentCommentId');
                $table->string('content_id', length: 36)->notNull()->index();
                $table->string('user_id', length: 36)->index();
                $table->string('parent_id', length: 36)->index();
                $table->text('comment_body')->notNull();
                $table->string('comment_status', length: 36)->notNull()->defaultValue('open');
                $table->string('comment_type', length: 36)->notNull()->defaultValue('editorial');
                $table->text('selection_json')->size('big');
                $table->dateTime('created_at')->notNull();
                $table->dateTime('updated_at');

                $table->foreign(columns: 'content_id', name: $prefix . 'fx_content_comment_cid')
                    ->references($prefix . 'content', 'content_id')
                    ->onDelete(action: 'cascade')
                    ->onUpdate(action: 'cascade');

                $table->foreign(columns: 'user_id', name: $prefix . 'fx_content_comment_uid')
                    ->references($dfdb->basePrefix . 'user', 'user_id')
                    ->onDelete(action: 'set null')
                    ->onUpdate(action: 'cascade');

                $table->foreign(columns: 'parent_id', name: $prefix . 'fx_content_comment_pid')
                    ->references($prefix . 'content_comment', 'comment_id')
                    ->onDelete(action: 'cascade')
                    ->onUpdate(action: 'cascade');
            });
        }
    }
}
