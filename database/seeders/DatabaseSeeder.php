<?php

declare(strict_types=1);

namespace Database\Seeders;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Qubus\Expressive\Migration\Seeder\BaseSeeder;
use Qubus\Expressive\Migration\Seeder\SeederContext;

final class DatabaseSeeder extends BaseSeeder
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function run(SeederContext $context): void
    {
        $this->call(seeder: UserSeeder::class, context: $context);
    }
}
