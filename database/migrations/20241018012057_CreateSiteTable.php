<?php

declare(strict_types=1);

use Qubus\Exception\Exception;
use Qubus\Expressive\Migration\Migration;
use Qubus\Expressive\Schema\CreateTable;

use function Codefy\Framework\Helpers\config;

class CreateSiteTable extends Migration
{
    /**
     * Do the migration
     * @throws Exception
     */
    public function up(): void
    {
        $default = config()->string(key: 'database.default');
        $tablePrefix = config()->string(key: "database.connections.{$default}.prefix");

        if (!$this->schema()->hasTable(table: $tablePrefix . 'site')) {
            $this->schema()
                ->create(table: $tablePrefix . 'site', callback: function (CreateTable $table) use ($tablePrefix) {
                    $table->string(name: 'site_id', length: 36)
                        ->primary(name: 'siteId')
                        ->unique(name: $tablePrefix . 'siteId');
                    $table->string(name: 'site_key', length: 191)
                        ->unique(name: $tablePrefix . 'siteKey');
                    $table->string(name: 'site_name', length: 191);
                    $table->string(name: 'site_slug', length: 191);
                    $table->string(name: 'site_domain', length: 191);
                    $table->string(name: 'site_mapping', length: 191);
                    $table->string(name: 'site_path', length: 191);
                    $table->string(name: 'site_owner', length: 36);
                    $table->string(name: 'site_status', length: 191);
                    $table->dateTime('site_registered');
                    $table->dateTime('site_modified');
                    $table->unique(['site_slug', 'site_domain', 'site_path'], $tablePrefix . 'siteIndex');

                    $table->foreign(columns: 'site_owner', name: $tablePrefix . 'siteOwner')
                        ->references($tablePrefix . 'user', 'user_id')
                        ->onDelete('cascade')
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
        $default = config()->string(key: 'database.default');
        $tablePrefix = config()->string(key: "database.connections.{$default}.prefix");

        if ($this->schema()->hasTable(table: $tablePrefix . 'site')) {
            $this->schema()->drop(table: $tablePrefix . 'site');
        }
    }
}
