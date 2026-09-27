<?php

declare(strict_types=1);

namespace Application\Provider;

use Codefy\Framework\Support\CodefyServiceProvider;
use Qubus\Exception\Data\TypeException;

final class MiddlewareAliasServiceProvider extends CodefyServiceProvider
{
    /**
     * @throws TypeException
     */
    public function boot(): void
    {
        // The PSR router resolves aliases through the container. Bind the final map
        // after providers have added site/plugin aliases to the application configuration.
        foreach ($this->codefy->configContainer->array('app.middlewares', []) as $alias => $class) {
            $this->codefy->alias($alias, $class);
        }
    }
}
