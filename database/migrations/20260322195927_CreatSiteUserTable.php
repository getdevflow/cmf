<?php

declare(strict_types=1);

use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use Qubus\Expressive\Migration\Migration;
use Qubus\Expressive\Schema\CreateTable;

use function Codefy\Framework\Helpers\config;

class CreatSiteUserTable extends Migration
{
    /**
     * Do the migration
     * @throws TypeException
     * @throws Exception
     */
    public function up(): void
    {
        $default = config()->string(key: 'database.default');
        $tablePrefix = config()->string(key: "database.connections.{$default}.prefix");

        if (!$this->schema()->hasTable(table: $tablePrefix . 'site_user')) {
            $this->schema()
                ->create(table: $tablePrefix . 'site_user', callback: function (CreateTable $table) use ($tablePrefix) {
                    $table->string(name: 'site_id', length: 191);
                    $table->string(name: 'user_id', length: 191);
                    $table->text(name: 'user_attribute')->size(value: 'big');
                    $table->unique(['site_id', 'user_id'], $tablePrefix . 'siteUser');

                    $table->foreign(columns: 'site_id', name: $tablePrefix . 'suSiteId')
                        ->references($tablePrefix . 'site', 'site_id')
                        ->onDelete('cascade')
                        ->onUpdate('cascade');

                    $table->foreign(columns: 'user_id', name: $tablePrefix . 'suUserId')
                        ->references($tablePrefix . 'user', 'user_id')
                        ->onDelete('cascade')
                        ->onUpdate('cascade');
                });
        }
    }

    /**
     * Undo the migration
     * @throws TypeException
     * @throws Exception
     */
    public function down(): void
    {
        $default = config()->string(key: 'database.default');
        $tablePrefix = config()->string(key: "database.connections.{$default}.prefix");

        if ($this->schema()->hasTable(table: $tablePrefix . 'site_user')) {
            $this->schema()->drop(table: $tablePrefix . 'site_user');
        }
    }
}
