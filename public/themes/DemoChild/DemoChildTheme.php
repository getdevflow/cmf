<?php

declare(strict_types=1);

namespace Theme\DemoChild;

use App\Shared\Services\Registry;
use ReflectionException;
use Theme\Demo\DemoTheme;

use function App\Shared\Helpers\theme_root;
use function App\Shared\Helpers\theme_url;
use function Qubus\Security\Helpers\t__;

final class DemoChildTheme extends DemoTheme
{
    public function meta(): array
    {
        $theme = [
            'name' => t__(msgid: 'Demo Child', domain: 'demo-child'),
            'id' => 'demo-child',
            'slug' => 'DemoChild',
            'author' => 'Joshua Parker',
            'version' => '1.0.0',
            'description' => t__(
                msgid: 'Example child theme inheriting Demo layouts and assets.',
                domain: 'demo-child'
            ),
            'basename' => basename(__DIR__),
            'path' => theme_root(__FILE__),
            'url' => theme_url('', self::class),
            'themeUri' => '',
            'authorUri' => 'https://joshuaparker.dev/',
            'className' => self::class,
            'screenshot' => theme_url('Demo/images/screenshot.png'),
        ];

        Registry::getInstance()->set('demo-child', $theme);

        return $theme;
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public function handle(): void
    {
        // Preserve the parent's pagebuilder.support filter and other initialization.
        parent::handle();
    }
}
