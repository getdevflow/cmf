<?php

declare(strict_types=1);

namespace App\Domain\Product\Command;

use App\Domain\Product\ValueObject\ProductId;
use App\Domain\User\ValueObject\UserId;
use App\Shared\ValueObject\ArrayLiteral;
use Codefy\CommandBus\PropertyCommand;
use DateTimeInterface;
use Qubus\ValueObjects\Money\Money;
use Qubus\ValueObjects\Number\IntegerNumber;
use Qubus\ValueObjects\StringLiteral\StringLiteral;

class UpdateProductCommand extends PropertyCommand
{
    public ?ProductId $id = null;

    public ?StringLiteral $title = null;

    public ?StringLiteral $slug = null;

    public ?StringLiteral $body = null;

    public ?UserId $author = null;

    public ?StringLiteral $sku = null;

    public ?Money $price = null;

    public ?StringLiteral $purchaseUrl = null;

    public ?IntegerNumber $showInMenu = null;

    public ?IntegerNumber $showInSearch = null;

    public ?StringLiteral $featuredImage = null;

    public ?StringLiteral $status = null;

    public ?ArrayLiteral $meta = null;

    public ?DateTimeInterface $published = null;

    public ?DateTimeInterface $publishedGmt = null;

    public ?DateTimeInterface $modified = null;

    public ?DateTimeInterface $modifiedGmt = null;
}
