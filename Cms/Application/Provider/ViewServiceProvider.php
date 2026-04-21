<?php

declare(strict_types=1);

namespace Application\Provider;

use Codefy\Framework\Support\CodefyServiceProvider;
use Qubus\View\Native\NativeLoader;
use Qubus\View\Renderer;

final class ViewServiceProvider extends CodefyServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->codefy->singleton(Renderer::class, function () {
            return new NativeLoader(
                namespaces: $this->codefy->configContainer->array(key: 'view.path'),
                functions: [],
                extension: 'phtml'
            );
        });
    }
}
