<?php

declare(strict_types=1);

use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use Qubus\Expressive\Database;
use Qubus\Expressive\Schema\CreateTable;

final class CreateSiteContentNotificationTable
{
    /**
     * Do the migration
     *
     * @throws Exception
     * @throws TypeException
     */
    public function up(Database $dfdb, string $prefix): void
    {
        if (!$dfdb->schema()->hasTable(table: $prefix . 'content_notification')) {
            $dfdb->schema()->create(
                $prefix . 'content_notification',
                function (CreateTable $table) use ($dfdb, $prefix) {
                    $table->string('notification_id', length: 36)
                        ->primary()
                        ->unique($prefix . 'contentNotificationId');
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

                    $table->foreign(columns: 'content_id', name: $prefix . 'fx_content_notification_cid')
                        ->references($prefix . 'content', 'content_id')
                        ->onDelete(action: 'cascade')
                        ->onUpdate(action: 'cascade');

                    $table->foreign(columns: 'user_id', name: $prefix . 'fx_content_notification_uid')
                        ->references($dfdb->basePrefix . 'user', 'user_id')
                        ->onDelete(action: 'cascade')
                        ->onUpdate(action: 'cascade');

                    $table->foreign(columns: 'activity_id', name: $prefix . 'fx_content_notification_aid')
                        ->references($prefix . 'content_workflow_activity', 'activity_id')
                        ->onDelete(action: 'set null')
                        ->onUpdate(action: 'cascade');
                }
            );
        }
    }
}
