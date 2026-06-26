<?php

declare(strict_types=1);

use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use Qubus\Expressive\Database;
use Qubus\Expressive\Schema\CreateTable;

final class CreateSiteContentActivityTable
{
    /**
     * Do the migration
     *
     * @throws Exception
     * @throws TypeException
     */
    public function up(Database $dfdb, string $prefix): void
    {
        if (!$dfdb->schema()->hasTable(table: $prefix . 'content_workflow_activity')) {
            $dfdb->schema()->create(
                $prefix . 'content_workflow_activity',
                function (CreateTable $table) use ($dfdb, $prefix) {
                    $table->string('activity_id', length: 36)
                        ->primary()
                        ->unique($prefix . 'contentWorkflowActivityId');
                    $table->string('content_id', length: 36)->notNull()->index();
                    $table->string('user_id', length: 36)->index();
                    $table->string('activity_type', length: 50)->notNull()->index();
                    $table->string('from_status', length: 36);
                    $table->string('to_status', length: 36);
                    $table->text('message')->size('big');
                    $table->text('metadata')->size('big');
                    $table->dateTime('created_at')->notNull();

                    $table->foreign(columns: 'content_id', name: $prefix . 'fx_content_wactivtity_cid')
                        ->references($prefix . 'content', 'content_id')
                        ->onDelete(action: 'cascade')
                        ->onUpdate(action: 'cascade');

                    $table->foreign(columns: 'user_id', name: $prefix . 'fx_content_wactivtity_uid')
                        ->references($dfdb->basePrefix . 'user', 'user_id')
                        ->onDelete(action: 'set null')
                        ->onUpdate(action: 'cascade');
                }
            );
        }
    }
}
