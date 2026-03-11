<?php

declare(strict_types=1);

namespace Application\Http\Controller;

use Codefy\CommandBus\Exceptions\CommandPropertyNotFoundException;
use Codefy\CommandBus\Exceptions\UnresolvableCommandHandlerException;
use Codefy\Framework\Http\BaseController;
use Codefy\QueryBus\UnresolvableQueryHandlerException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use Qubus\Http\ServerRequest;
use ReflectionException;

use function App\Shared\Helpers\get_all_sites;
use function App\Shared\Helpers\publish_scheduled_content;
use function App\Shared\Helpers\publish_scheduled_product;
use function App\Shared\Helpers\restore_current_site;
use function App\Shared\Helpers\switch_to_site;

final class CronController extends BaseController
{
    /**
     * @param ServerRequest $request
     * @return void
     * @throws CommandPropertyNotFoundException
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws TypeException
     * @throws UnresolvableCommandHandlerException
     * @throws UnresolvableQueryHandlerException
     */
    public function cron(ServerRequest $request): void
    {
        foreach (get_all_sites() as $site) {
            switch_to_site($site['key']);
            publish_scheduled_content();
            publish_scheduled_product();
            restore_current_site();
        }
    }
}
