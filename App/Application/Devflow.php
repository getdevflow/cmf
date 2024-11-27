<?php

declare(strict_types=1);

namespace App\Application;

use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Services\NativePhpCookies;
use App\Infrastructure\Services\Options;
use App\Shared\Services\Image;
use Codefy\Framework\Application;
use Codefy\Framework\Codefy;
use Qubus\Support\ArrayHelper;
use Qubus\Support\Inflector;
use Qubus\Support\StringHelper;
use stdClass;

/**
 * @property-read Database $dfdb
 * @property-read Image $image
 * @property-read Options $option
 * @property-read NativePhpCookies $cookies
 * @property-read StringHelper $string
 * @property-read ArrayHelper $array
 * @property-read Inflector $inflect
 */
final class Devflow extends stdClass
{
    public static Application|null|Devflow $APP = null;

    private function __construct()
    {
        Devflow::$APP = Codefy::$PHP;
        $this->init();
    }

    public static function inst(): Application|Devflow
    {
        return new self();
    }

    private function init(): void
    {
        $aliases = [
            'dfdb' => Database::class,
            'image' => Image::class,
            'option' => Options::class,
            'cookies' => NativePhpCookies::class,
            'string' => StringHelper::class,
            'array' => ArrayHelper::class,
        ];

        foreach ($aliases as $property => $name) {
            $this->{$property} = Application::$APP->make(name: $name);
        }
    }

    public function release(): string
    {
        return '1.0.0-rc.2';
    }
}
