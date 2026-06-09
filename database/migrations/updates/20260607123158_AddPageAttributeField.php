<?php

declare(strict_types=1);

use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use Qubus\Expressive\Database;
use Qubus\Expressive\Schema\AlterTable;

final class AddPageAttributeField
{
    /**
     * Do the migration
     *
     * @throws Exception
     * @throws TypeException
     */
    public function up(Database $dfdb, string $prefix): void
    {
        $table = $prefix . 'pages';

        $dfdb->schema()->alter(table: $table, callback: function (AlterTable $table): void {
            $table->text(name: 'page_attribute')->size(value: 'big');
        });
    }
}
