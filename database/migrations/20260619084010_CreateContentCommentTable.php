<?php

declare(strict_types=1);

use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use Qubus\Expressive\Migration\Migration;
use Qubus\Expressive\Schema\CreateTable;

use function Codefy\Framework\Helpers\config;

class CreateContentCommentTable extends Migration
{
    /**
     * Do the migration.
     *
     * @throws Exception
     * @throws TypeException
     */
    public function up(): void
    {
        $default = config()->string(key: 'database.default');
        $tablePrefix = config()->string(key: "database.connections.{$default}.prefix");

        if (!$this->schema()->hasTable(table: $tablePrefix . 'content_comment')) {
            $this->schema()->create($tablePrefix . 'content_comment', function (CreateTable $table) use ($tablePrefix) {
                $table->string('comment_id', length: 36)
                    ->primary()
                    ->unique($tablePrefix . 'contentCommentId');
                $table->string('content_id', length: 36)->notNull()->index();
                $table->string('user_id', length: 36)->index();
                $table->string('parent_id', length: 36)->index();
                $table->text('comment_body')->notNull();
                $table->string('comment_status', length: 36)->notNull()->defaultValue('open');
                $table->string('comment_type', length: 36)->notNull()->defaultValue('editorial');
                $table->text('selection_json')->size('big');
                $table->dateTime('created_at')->notNull();
                $table->dateTime('updated_at');

                $table->foreign(columns: 'content_id', name: $tablePrefix . 'fx_content_comment_cid')
                    ->references($tablePrefix . 'content', 'content_id')
                    ->onDelete(action: 'cascade')
                    ->onUpdate(action: 'cascade');

                $table->foreign(columns: 'user_id', name: $tablePrefix . 'fx_content_comment_uid')
                    ->references($tablePrefix . 'user', 'user_id')
                    ->onDelete(action: 'set null')
                    ->onUpdate(action: 'cascade');

                $table->foreign(columns: 'parent_id', name: $tablePrefix . 'fx_content_comment_pid')
                    ->references($tablePrefix . 'content_comment', 'comment_id')
                    ->onDelete(action: 'cascade')
                    ->onUpdate(action: 'cascade');
            });
        }
    }

    /**
     * Undo the migration.
     *
     * @throws TypeException
     * @throws Exception
     */
    public function down(): void
    {
        $default = config()->string(key: 'database.default');
        $tablePrefix = config()->string(key: "database.connections.{$default}.prefix");

        if ($this->schema()->hasTable(table: $tablePrefix . 'content_comment')) {
            $this->schema()->drop(table: $tablePrefix . 'content_comment');
        }
    }
}
