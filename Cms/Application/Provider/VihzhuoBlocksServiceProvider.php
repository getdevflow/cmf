<?php

declare(strict_types=1);

namespace Application\Provider;

use Codefy\Framework\Support\CodefyServiceProvider;

use function Codefy\Framework\Helpers\resource_path;

final class VihzhuoBlocksServiceProvider extends CodefyServiceProvider
{
    public function register(): void
    {
        //hidden
        \Vihzhuo\Extensions::registerBlock(
            slug: 'blocks-container',
            directoryPath: resource_path(path: 'blocks/layout-blocks-container')
        );
        \Vihzhuo\Extensions::registerBlock(
            slug: 'accordion-header',
            directoryPath: resource_path(path: 'blocks/accordion-header')
        );
        \Vihzhuo\Extensions::registerBlock(
            slug: 'accordion-body',
            directoryPath: resource_path(path: 'blocks/accordion-body')
        );
        \Vihzhuo\Extensions::registerBlock(
            slug: 'carousel-item',
            directoryPath: resource_path(path: 'blocks/carousel-item')
        );
        \Vihzhuo\Extensions::registerBlock(
            slug: 'card-heading',
            directoryPath: resource_path(path: 'blocks/card-heading')
        );
        \Vihzhuo\Extensions::registerBlock(
            slug: 'card-text',
            directoryPath: resource_path(path: 'blocks/card-text')
        );
        \Vihzhuo\Extensions::registerBlock(
            slug: 'alert-text',
            directoryPath: resource_path(path: 'blocks/alert-text')
        );

        // Layout
        \Vihzhuo\Extensions::registerBlock(
            slug: 'container',
            directoryPath: resource_path(path: 'blocks/container')
        );
        \Vihzhuo\Extensions::registerBlock(
            slug: 'grid',
            directoryPath: resource_path(path: 'blocks/grid')
        );

        // Basic
        \Vihzhuo\Extensions::registerBlock(
            slug: 'heading',
            directoryPath: resource_path(path: 'blocks/heading')
        );
        \Vihzhuo\Extensions::registerBlock(
            slug: 'image',
            directoryPath: resource_path(path: 'blocks/image')
        );
        \Vihzhuo\Extensions::registerBlock(
            slug: 'text',
            directoryPath: resource_path(path: 'blocks/text')
        );
        \Vihzhuo\Extensions::registerBlock(
            slug: 'youtube',
            directoryPath: resource_path(path: 'blocks/youtube')
        );
        \Vihzhuo\Extensions::registerBlock(
            slug: 'button',
            directoryPath: resource_path(path: 'blocks/button')
        );
        \Vihzhuo\Extensions::registerBlock(
            slug: 'link',
            directoryPath: resource_path(path: 'blocks/link')
        );
        \Vihzhuo\Extensions::registerBlock(
            slug: 'divider',
            directoryPath: resource_path(path: 'blocks/divider')
        );
        \Vihzhuo\Extensions::registerBlock(
            slug: 'google-map',
            directoryPath: resource_path(path: 'blocks/google-map')
        );
        \Vihzhuo\Extensions::registerBlock(
            slug: 'spacer',
            directoryPath: resource_path(path: 'blocks/spacer')
        );
        \Vihzhuo\Extensions::registerBlock(
            slug: 'lead',
            directoryPath: resource_path(path: 'blocks/lead')
        );

        // Components
        \Vihzhuo\Extensions::registerBlock(
            slug: 'card',
            directoryPath: resource_path(path: 'blocks/card')
        );
        \Vihzhuo\Extensions::registerBlock(
            slug: 'accordion',
            directoryPath: resource_path(path: 'blocks/accordion')
        );
        \Vihzhuo\Extensions::registerBlock(
            slug: 'content-image',
            directoryPath: resource_path(path: 'blocks/content-image')
        );
        \Vihzhuo\Extensions::registerBlock(
            slug: 'carousel',
            directoryPath: resource_path(path: 'blocks/carousel')
        );
        \Vihzhuo\Extensions::registerBlock(
            slug: 'alert',
            directoryPath: resource_path(path: 'blocks/alert')
        );

        // Forms
        \Vihzhuo\Extensions::registerBlock(
            slug: 'regular-form',
            directoryPath: resource_path(path: 'blocks/forms/form')
        );
        \Vihzhuo\Extensions::registerBlock(
            slug: 'form-input',
            directoryPath: resource_path(path: 'blocks/forms/input')
        );
        \Vihzhuo\Extensions::registerBlock(
            slug: 'checkbox',
            directoryPath: resource_path(path: 'blocks/forms/checkbox')
        );
        \Vihzhuo\Extensions::registerBlock(
            slug: 'textarea',
            directoryPath: resource_path(path: 'blocks/forms/textarea')
        );
    }
}
