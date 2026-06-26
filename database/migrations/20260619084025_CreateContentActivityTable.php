<?php

declare(strict_types=1);

use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use Qubus\Expressive\Migration\Migration;
use Qubus\Expressive\Schema\CreateTable;

use function Codefy\Framework\Helpers\config;

class CreateContentActivityTable extends Migration
{
    /**
     * Do the migration.
     *
     * @throws TypeException
     * @throws Exception
     */
    public function up(): void
    {
        $default = config()->string(key: 'database.default');
        $tablePrefix = config()->string(key: "database.connections.{$default}.prefix");

        if (!$this->schema()->hasTable(table: $tablePrefix . 'content_workflow_activity')) {
            $this->schema()->create(
                $tablePrefix . 'content_workflow_activity',
                function (CreateTable $table) use ($tablePrefix) {
                    $table->string('activity_id', length: 36)
                        ->primary()
                        ->unique($tablePrefix . 'contentWorkflowActivityId');
                    $table->string('content_id', length: 36)->notNull()->index();
                    $table->string('user_id', length: 36)->index();
                    $table->string('activity_type', length: 50)->notNull()->index();
                    $table->string('from_status', length: 36);
                    $table->string('to_status', length: 36);
                    $table->text('message')->size('big');
                    $table->text('metadata')->size('big');
                    $table->dateTime('created_at')->notNull();

                    $table->foreign(columns: 'content_id', name: $tablePrefix . 'fx_content_wactivtity_cid')
                        ->references($tablePrefix . 'content', 'content_id')
                        ->onDelete(action: 'cascade')
                        ->onUpdate(action: 'cascade');

                    $table->foreign(columns: 'user_id', name: $tablePrefix . 'fx_content_wactivtity_uid')
                        ->references($tablePrefix . 'user', 'user_id')
                        ->onDelete(action: 'set null')
                        ->onUpdate(action: 'cascade');
                }
            );
        }
    }

    /**
     * Undo the migration.
     *
     * @throws Exception
     * @throws TypeException
     */
    public function down(): void
    {
        $default = config()->string(key: 'database.default');
        $tablePrefix = config()->string(key: "database.connections.{$default}.prefix");

        if ($this->schema()->hasTable(table: $tablePrefix . 'content_workflow_activity')) {
            $this->schema()->drop(table: $tablePrefix . 'content_workflow_activity');
        }
    }
}
