<?php

declare(strict_types=1);

namespace App\Domain\Content\Command;

use App\Domain\Content\Content;
use App\Domain\Content\Repository\ContentRepository;
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
    public function __construct(public ContentRepository $aggregateRepository)
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
        /** @var Content $content */
        $content = $this->aggregateRepository->loadAggregateRoot($command->contentId);

        $content->changeContentFeaturedImage($command->contentFeaturedImage);
        $content->changeContentModified(QubusDateTimeImmutable::now(tz: get_user_timezone()));
        $content->changeContentModifiedGmt(QubusDateTimeImmutable::now(tz: 'GMT'));

        $this->aggregateRepository->saveAggregateRoot($content);
    }
}
