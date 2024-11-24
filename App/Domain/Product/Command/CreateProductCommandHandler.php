<?php

declare(strict_types=1);

namespace App\Domain\Product\Command;

use App\Domain\Product\Product;
use App\Domain\Product\Repository\ProductRepository;
use Codefy\CommandBus\Command;
use Codefy\CommandBus\CommandHandler;
use Qubus\Exception\Data\TypeException;

class CreateProductCommandHandler implements CommandHandler
{
    public function __construct(public ProductRepository $aggregateRepository)
    {
    }

    /**
     * @inheritDoc
     * @throws TypeException
     */
    public function handle(CreateProductCommand|Command $command): void
    {
        $product = Product::createProduct(
            $command->id,
            $command->title,
            $command->slug,
            $command->body,
            $command->author,
            $command->sku,
            $command->price,
            $command->purchaseUrl,
            $command->showInMenu,
            $command->showInSearch,
            $command->featuredImage,
            $command->status,
            $command->created,
            $command->createdGmt,
            $command->published,
            $command->publishedGmt,
            $command->meta,
        );

        $this->aggregateRepository->saveAggregateRoot($product);
    }
}
