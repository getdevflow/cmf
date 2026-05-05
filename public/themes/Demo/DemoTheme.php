<?php

declare(strict_types=1);

namespace Theme\Demo;

use App\Infrastructure\Services\Theme;
use App\Shared\Services\Registry;
use Qubus\Exception\Exception;
use ReflectionException;

use function App\Shared\Helpers\theme_root;
use function App\Shared\Helpers\theme_url;
use function basename;
use function dirname;
use function get_class;
use function Qubus\Security\Helpers\t__;

final class DemoTheme extends Theme
{
    /**
     * @inheritDoc
     * @throws ReflectionException|Exception
     */
    public function meta(): array
    {
        $theme = [
            'name' => t__(msgid: 'Demo Theme', domain: 'demo'),
            'id' => 'demo',
            'slug' => 'Demo',
            'author' => 'Joshua Parker',
            'version' => '1.0.0',
            'description' => t__(
                msgid: 'A demo theme to get started.',
                domain: 'demo'
            ),
            'basename' => basename(dirname(__FILE__)),
            'path' => theme_root(__FILE__),
            'url' => theme_url('', __CLASS__),
            'themeUri' => 'https://github.com/getdevflow/bootstrap-business',
            'authorUri' => 'https://joshuaparker.dev/',
            'className' => get_class($this),
            'screenshot' => theme_url('Demo/images/screenshot.png'),
        ];

        Registry::getInstance()->set('demo', $theme);

        return $theme;
    }

    /**
     * @inheritDoc
     */
    public function handle(): void
    {
    }
}
