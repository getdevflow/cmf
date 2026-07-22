<?php

declare(strict_types=1);

use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use Qubus\Expressive\Migration\Migration;
use Qubus\Expressive\Schema\CreateTable;

use function Codefy\Framework\Helpers\config;

class CreateContentNotificationTable extends Migration
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

        if (!$this->schema()->hasTable(table: $tablePrefix . 'content_notification')) {
            $this->schema()->create(
                $tablePrefix . 'content_notification',
                function (CreateTable $table) use ($tablePrefix) {
                    $table->string('notification_id', length: 36)
                        ->primary()
                        ->unique($tablePrefix . 'contentNotificationId');
                    $table->string('content_id', length: 36)->notNull()->index();
                    $table->string('user_id', length: 36)->notNull()->index();
                    $table->string('activity_id', length: 36)->index();
                    $table->string('notification_type', length: 80)->notNull();
                    $table->string('title', length: 191)->notNull();
                    $table->text('body')->size('big');
                    $table->integer('is_read')->notNull()->defaultValue(0);
                    $table->dateTime('created_at')->notNull();
                    $table->dateTime('read_at');
                    $table->index(['user_id', 'is_read']);

                    $table->foreign(columns: 'content_id', name: $tablePrefix . 'fx_content_notification_cid')
                        ->references($tablePrefix . 'content', 'content_id')
                        ->onDelete(action: 'cascade')
                        ->onUpdate(action: 'cascade');

                    $table->foreign(columns: 'user_id', name: $tablePrefix . 'fx_content_notification_uid')
                        ->references($tablePrefix . 'user', 'user_id')
                        ->onDelete(action: 'cascade')
                        ->onUpdate(action: 'cascade');

                    $table->foreign(columns: 'activity_id', name: $tablePrefix . 'fx_content_notification_aid')
                        ->references($tablePrefix . 'content_workflow_activity', 'activity_id')
                        ->onDelete(action: 'set null')
                        ->onUpdate(action: 'cascade');
                }
            );
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

        if ($this->schema()->hasTable(table: $tablePrefix . 'content_notification')) {
            $this->schema()->drop(table: $tablePrefix . 'content_notification');
        }
    }
}
