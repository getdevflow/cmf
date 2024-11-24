<?php

declare(strict_types=1);

use Codefy\Framework\Migration\Migration;
use Qubus\Dbal\Schema\CreateTable;
use Qubus\Exception\Exception;

use function Codefy\Framework\Helpers\config;

class CreateUserTable extends Migration
{
    /**
     * Do the migration
     * @throws Exception
     */
    public function up(): void
    {
        $tablePrefix = config(key: 'database.connections.default.prefix');

        if (!$this->schema()->hasTable(table: $tablePrefix . 'user')) {
            $this->schema()
                ->create(table: $tablePrefix . 'user', callback: function (CreateTable $table) use ($tablePrefix) {
                    $table->string(name: 'user_id', length: 36)
                        ->primary(name: 'userId')
                        ->unique(name: $tablePrefix . 'userId');
                    $table->string(name: 'user_login', length: 191)
                        ->unique(name: $tablePrefix . 'userLogin')
                        ->notNull();
                    $table->string(name: 'user_fname', length: 191);
                    $table->string(name: 'user_mname', length: 191);
                    $table->string(name: 'user_lname', length: 191);
                    $table->string(name: 'user_email', length: 191)
                        ->unique(name: $tablePrefix . 'userEmail')
                        ->notNull();
                    $table->string(name: 'user_pass', length: 191)
                        ->notNull();
                    $table->string(name: 'user_token', length: 36)
                        ->unique(name: $tablePrefix . 'userToken')
                        ->notNull();
                    $table->string(name: 'user_url', length: 191);
                    $table->string(name: 'user_timezone', length: 191);
                    $table->string(name: 'user_date_format', length: 191);
                    $table->string(name: 'user_time_format', length: 191);
                    $table->string(name: 'user_locale', length: 191);
                    $table->string(name: 'user_activation_key', length: 191);
                    $table->dateTime(name: 'user_registered');
                    $table->dateTime(name: 'user_modified')->defaultValue('0000-00-00 00:00:00');
                });
        }
    }

    /**
     * Undo the migration
     * @throws Exception
     */
    public function down(): void
    {
        $tablePrefix = config(key: 'database.connections.default.prefix');

        if ($this->schema()->hasTable(table: $tablePrefix . 'user')) {
            $this->schema()->drop(table: $tablePrefix . 'user');
        }
    }
}
