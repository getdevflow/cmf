<?php

declare(strict_types=1);

namespace Application\Provider;

use Codefy\Framework\Support\CodefyServiceProvider;
use Psr\EventDispatcher\ListenerProviderInterface;

final class EventListenerServiceProvider extends CodefyServiceProvider
{
    /**
     * @return void
     */
    public function boot(): void
    {
        /** @var ListenerProviderInterface $provider */
        $provider = $this->codefy->make(name: ListenerProviderInterface::class);
    }
}
