<?php

declare(strict_types=1);

namespace App\Infrastructure\Providers;

use Codefy\Framework\Support\CodefyServiceProvider;
use Qubus\Exception\Exception;

class MiddlewareServiceProvider extends CodefyServiceProvider
{
    /**
     * @throws Exception
     */
    public function register(): void
    {
        if ($this->codefy->isRunningInConsole()) {
            return;
        }

        $middlewares = $this->codefy->configContainer->getConfigKey(key: 'app.middlewares');
        foreach ($middlewares as $key => $value) {
            $this->codefy->alias(original: $key, alias: $value);
        }
    }
}
