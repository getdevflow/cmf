<?php

declare(strict_types=1);

namespace App\Domain\Product\Command;

use App\Domain\Product\Product;
use App\Domain\Product\Repository\ProductRepository;
use Codefy\CommandBus\Command;
use Codefy\CommandBus\CommandHandler;
use Codefy\Domain\Aggregate\AggregateNotFoundException;
use Exception;

class DeleteProductCommandHandler implements CommandHandler
{
    public function __construct(public ProductRepository $aggregateRepository)
    {
    }

    /**
     * @inheritDoc
     * @throws AggregateNotFoundException
     * @throws Exception
     */
    public function handle(DeleteProductCommand|Command $command): void
    {
        /** @var Product $product */
        $product = $this->aggregateRepository->loadAggregateRoot($command->productId);

        $product->changeProductDeleted($command->productId);

        $this->aggregateRepository->saveAggregateRoot($product);
    }
}
