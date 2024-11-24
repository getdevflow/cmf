<?php

declare(strict_types=1);

namespace App\Domain\Product\Command;

use App\Domain\Product\Product;
use App\Domain\Product\Repository\ProductRepository;
use Codefy\CommandBus\Command;
use Codefy\CommandBus\CommandHandler;
use Codefy\Domain\Aggregate\AggregateNotFoundException;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Qubus\Support\DateTime\QubusDateTimeImmutable;
use ReflectionException;

use function App\Shared\Helpers\get_user_timezone;

class RemoveFeaturedImageCommandHandler implements CommandHandler
{
    public function __construct(public ProductRepository $aggregateRepository)
    {
    }

    /**
     * @inheritDoc
     * @param RemoveFeaturedImageCommand|Command $command
     * @throws AggregateNotFoundException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     * @throws \Qubus\Exception\Exception
     * @throws ReflectionException
     * @throws Exception
     */
    public function handle(RemoveFeaturedImageCommand|Command $command): void
    {
        /** @var Product $product */
        $product = $this->aggregateRepository->loadAggregateRoot($command->productId);

        $product->changeProductFeaturedImage($command->productFeaturedImage);
        $product->changeProductModified(QubusDateTimeImmutable::now(tz: get_user_timezone()));
        $product->changeProductModifiedGmt(QubusDateTimeImmutable::now(tz: get_user_timezone()));

        $this->aggregateRepository->saveAggregateRoot($product);
    }
}
