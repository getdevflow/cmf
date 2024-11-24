<?php

declare(strict_types=1);

use Codefy\Framework\Migration\Migration;
use Qubus\Dbal\Schema\CreateTable;
use Qubus\Exception\Exception;

use function Codefy\Framework\Helpers\config;
use function Qubus\Config\Helpers\env;

class CreateContentTypeTable extends Migration
{
    /**
     * Do the migration
     * @throws Exception
     */
    public function up(): void
    {
        $tablePrefix = config('database.connections.default.prefix');

        if (!$this->schema()->hasTable(table: $tablePrefix . 'content_type')) {
            $this->schema()
                ->create(
                    table: $tablePrefix . 'content_type',
                    callback: function (CreateTable $table) use ($tablePrefix) {
                        $table->string(name: 'content_type_id', length: 36)
                            ->primary(name: 'contentTypeId')
                            ->unique(name: $tablePrefix . 'contentTypeId');
                        $table->string(name: 'content_type_title', length: 191);
                        $table->string(name: 'content_type_slug', length: 191);
                        $table->text(name: 'content_type_description')->size(value: 'big');
                        $table->index('content_type_slug', $tablePrefix . 'contentTypeIndex');
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
        $tablePrefix = config('database.connections.default.prefix');

        if ($this->schema()->hasTable(table: $tablePrefix . 'content_type')) {
            $this->schema()->drop(table: $tablePrefix . 'content)_type');
        }
    }
}
