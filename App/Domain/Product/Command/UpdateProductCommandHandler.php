<?php

declare(strict_types=1);

namespace App\Domain\Product\Command;

use App\Domain\Product\Product;
use App\Domain\Product\Repository\ProductRepository;
use Codefy\CommandBus\Command;
use Codefy\CommandBus\CommandHandler;
use Codefy\Domain\Aggregate\AggregateNotFoundException;
use Qubus\Exception\Exception;

class UpdateProductCommandHandler implements CommandHandler
{
    public function __construct(public ProductRepository $aggregateRepository)
    {
    }

    /**
     * @inheritDoc
     * @throws AggregateNotFoundException
     * @throws Exception
     */
    public function handle(UpdateProductCommand|Command $command): void
    {
        /** @var Product $product */
        $product = $this->aggregateRepository->loadAggregateRoot($command->id);

        $product->changeProductTitle($command->title);
        $product->changeProductSlug($command->slug);
        $product->changeProductBody($command->body);
        $product->changeProductAuthor($command->author);
        $product->changeProductSku($command->sku);
        $product->changeProductPrice($command->price);
        $product->changeProductPurchaseUrl($command->purchaseUrl);
        $product->changeProductShowInMenu($command->showInMenu);
        $product->changeProductShowInSearch($command->showInSearch);
        $product->changeProductFeaturedImage($command->featuredImage);
        $product->changeProductStatus($command->status);
        $product->changeProductMeta($command->meta);
        $product->changeProductPublished($command->published);
        $product->changeProductPublishedGmt($command->publishedGmt);
        if ($product->hasRecordedEvents()) {
            $product->changeProductModified($command->modified);
            $product->changeProductModifiedGmt($command->modifiedGmt);
        }

        $this->aggregateRepository->saveAggregateRoot($product);
    }
}
