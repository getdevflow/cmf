<?php

declare(strict_types=1);

use Codefy\Framework\Migration\Migration;
use Qubus\Dbal\Schema\CreateTable;
use Qubus\Exception\Exception;

use function Codefy\Framework\Helpers\config;

class CreateEventStoreTable extends Migration
{
    /**
     * Do the migration
     * @throws Exception
     */
    public function up(): void
    {
        $tablePrefix = config(key: 'database.connections.default.prefix');

        if (!$this->schema()->hasTable(table: $tablePrefix . 'event_store')) {
            $this->schema()->create(
                table: $tablePrefix . 'event_store',
                callback: function (CreateTable $table) use ($tablePrefix) {
                    $table->string(
                        name: 'event_id',
                        length: 36
                    )->primary(name: 'eventId')->unique(name: $tablePrefix . 'eventId');
                    $table->string(name: 'transaction_id', length: 36);
                    $table->string(name: 'event_type', length: 191)->notNull();
                    $table->string(name: 'event_classname', length: 191)->notNull();
                    $table->string(name: 'site', length: 191)->notNull();
                    $table->text(name: 'payload')->size(value: 'big')->notNull();
                    $table->text(name: 'metadata')->size(value: 'big')->notNull();
                    $table->string(name: 'aggregate_id', length: 36)->notNull();
                    $table->string(name: 'aggregate_type', length: 191)->notNull();
                    $table->integer(name: 'aggregate_playhead')->size(value: 'large')->notNull();
                    $table->dateTime(name: 'recorded_at')->notNull();
                    $table->unique(
                        columns: ['site','aggregate_id','aggregate_type','aggregate_playhead'],
                        name: $tablePrefix . 'domainEvent'
                    );
                }
            );
        }
    }

    /**
     * Undo the migration
     * @throws Exception
     */
    public function down(): void
    {
        $tablePrefix = config(key: 'database.connections.default.prefix');

        if ($this->schema()->hasTable(table: $tablePrefix . 'event_store')) {
            $this->schema()->drop(table: $tablePrefix . 'event_store');
        }
    }
}
