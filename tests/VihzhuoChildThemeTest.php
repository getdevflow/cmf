<?php

declare(strict_types=1);

use App\Infrastructure\Services\Vihzhuo\DevflowPageBuilder;
use App\Infrastructure\Services\Vihzhuo\VihzhuoTheme;
use Plugin\HeaderFooterBuilder\Controller\BuilderController;
use Plugin\HeaderFooterBuilder\Editor\TemplateEditor;
use Plugin\HeaderFooterBuilder\Editor\TemplateRenderer;
use Plugin\HeaderFooterBuilder\Support\Runtime;
use Qubus\EventDispatcher\ActionFilter\Filter;
use Qubus\Http\ServerRequest;
use Theme\IntegrationChild\IntegrationChildTheme;
use Vihzhuo\Core\ThemeContext;
use Vihzhuo\Modules\GrapesJS\PageRenderer;
use Vihzhuo\Page;
use Vihzhuo\ThemeLayout;

use function Codefy\Framework\Helpers\config;

require_once __DIR__ . '/Fixtures/Vihzhuo.php';
require_once __DIR__ . '/Fixtures/ChildThemes.php';

function childThemeFixture(): array
{
    [$repository, , $pdo, $directory] = hfbFixture();
    $files = [
        'IntegrationChild/IntegrationChildTheme.php' => '<?php namespace Theme\\IntegrationChild; final class IntegrationChildTheme extends \\Theme\\IntegrationParent\\IntegrationParentTheme {}',
        'IntegrationParent/IntegrationParentTheme.php' => '<?php namespace Theme\\IntegrationParent; class IntegrationParentTheme extends \\Theme\\Demo\\DemoTheme {}',
        'Demo/DemoTheme.php' => '<?php namespace Theme\\Demo; class DemoTheme extends \\App\\Infrastructure\\Services\\Theme {}',
        'Demo/layouts/master/config.php' => '<?php return ["title" => "Inherited master"];',
        'Demo/layouts/master/view.php' => '<html><head><link href="<?= phpb_theme_asset("css/style.css") ?>"></head><body><?= $body ?></body></html>',
        'Demo/blocks/header/config.php' => '<?php return ["title" => "Grandparent header"];',
        'Demo/blocks/header/view.html' => '<nav>Grandparent header</nav>',
        'IntegrationParent/blocks/header/config.php' => '<?php return ["title" => "Parent header"];',
        'IntegrationParent/blocks/header/view.html' => '<nav>Inherited parent header</nav>',
        'IntegrationChild/blocks/footer/config.php' => '<?php return ["title" => "Child footer"];',
        'IntegrationChild/blocks/footer/view.html' => '<footer>Child footer</footer>',
        'Demo/blocks/footer/config.php' => '<?php return ["title" => "Grandparent footer"];',
        'Demo/blocks/footer/view.html' => '<footer>Grandparent footer</footer>',
        'IntegrationParent/public/css/style.css' => '/* inherited */',
        'Demo/public/js/theme.js' => '/* inherited script */',
        'IntegrationChild/public/css/child.css' => '/* child */',
        'Demo/translations/en.php' => '<?php return ["child-theme-parent" => "Ancestor translation", "child-theme-override" => "Ancestor"];',
        'IntegrationChild/translations/en.php' => '<?php return ["child-theme-override" => "Child"];',
    ];
    foreach ($files as $path => $contents) {
        if (!is_dir(dirname($directory . '/' . $path))) {
            mkdir(dirname($directory . '/' . $path), 0700, true);
        }
        file_put_contents($directory . '/' . $path, $contents);
    }
    $settings = config()->array('vihzhuo');
    $settings['theme'] = ['class' => VihzhuoTheme::class, 'folder' => $directory,
        'folder_url' => '/themes', 'active_theme' => 'Demo'];
    $settings['storage']['use_database'] = true;
    config()->setConfigKey('vihzhuo', $settings);
    return [$repository, $settings, $pdo, $directory];
}

beforeEach(function () {
    $this->originalChildThemeConfig = config()->array('vihzhuo');
    $this->selectedChildTheme = IntegrationChildTheme::class;
    $this->childThemeFilter = fn () => $this->selectedChildTheme;
    Filter::getInstance()->addFilter('theme', $this->childThemeFilter);
    hfbRegisterThemeFilters();
});

afterEach(function () {
    Filter::getInstance()->removeFilter('theme', $this->childThemeFilter);
    hfbRemoveThemeFilters();
    config()->setConfigKey('vihzhuo', $this->originalChildThemeConfig);
    phpb_set_in_editmode(false);
});

it('uses the site selection for inherited layouts, editor blocks, assets and translations', function () {
    [, $settings, , $directory] = childThemeFixture();
    $builder = new DevflowPageBuilder($settings);
    $theme = $builder->getTheme();
    expect($theme)->toBeInstanceOf(VihzhuoTheme::class)
        ->and(array_keys($theme->getThemeFolders()))->toBe(['IntegrationChild', 'IntegrationParent', 'Demo'])
        ->and($theme->getThemeLayouts()['master']->getViewFile())->toBe($directory . '/Demo/layouts/master/view.php')
        ->and($theme->getThemeBlocks()['header']->get('title'))->toBe('Parent header')
        ->and($theme->getThemeBlocks()['footer']->get('title'))->toBe('Child footer')
        ->and(phpb_theme_asset('css/style.css'))->toBe('https://cms.test/themes/IntegrationParent/css/style.css')
        ->and(phpb_trans('child-theme-parent'))->toBe('Ancestor translation')
        ->and(phpb_trans('child-theme-override'))->toBe('Child');
    $page = new Page();
    $page->setData(['id' => 'child-page', 'layout' => 'master', 'data' => [
        'html' => ['[block slug="header" id="IDheader0000000000001"][block slug="footer" id="IDfooter0000000000001"]'],
        'css' => '', 'blocks' => [],
    ]]);
    $html = new PageRenderer($theme, $page)->render();
    expect($html)->toContain('Inherited parent header', 'Child footer', '/themes/IntegrationParent/css/style.css');
    phpb_set_in_editmode();
    $blocks = new PageRenderer($theme, $page, true)->getPageBlocksData();
    expect(json_encode($blocks))->toContain('Inherited parent header', 'Child footer');
});

it('keeps Header Footer Builder previews, AJAX and public slot replacement on the adapter', function () {
    [$repository, $settings] = childThemeFixture();
    $id = $repository->saveTemplate(['name' => 'Child header', 'kind' => 'header', 'published' => 1,
        'conditions' => ['global' => 1]]);
    $repository->saveData($id, ['html' => ['[block slug="footer" id="IDfooter0000000000002"]'], 'css' => '', 'blocks' => []]);
    $controller = new BuilderController($repository);
    $response = $controller->preview(new ServerRequest(queryParams: ['id' => $id]));
    expect($response->getStatusCode())->toBe(200)
        ->and((string) $response->getBody())->toContain('Child footer', '/themes/IntegrationParent/css/style.css', '/themes/Demo/js/theme.js');
    $mega = $repository->saveTemplate(['name' => 'Child mega', 'kind' => 'mega', 'published' => 1]);
    $repository->saveData($mega, ['html' => ['[block slug="footer" id="IDfooter0000000000003"]'], 'css' => '', 'blocks' => []]);
    expect((string) $controller->mega(new ServerRequest(), $mega)->getBody())->toContain('Child footer');
    $theme = Runtime::theme();
    $page = new Page();
    $page->setData(['id' => 'public-page', 'data' => ['html' => [], 'css' => '', 'blocks' => []]]);
    expect(new TemplateRenderer($theme, $page)->renderBlock('header'))->toContain('data-hfb-template', 'Child footer');
    $editor = new TemplateEditor();
    $editor->setTheme($theme);
    $fragment = $editor->renderPageBuilderBlock(Runtime::page($repository->template($id)), 'en', [
        'html' => '[block slug="header" id="IDheader0000000000003"]', 'blocks' => [],
    ]);
    expect((string) $fragment->getBody())->toContain('Inherited parent header');
    $settings['pagebuilder']['class'] = TemplateEditor::class;
    new DevflowPageBuilder($settings);
    // The editor's canvas layout uses the renderer's scoped theme for its assets.
    Vihzhuo\Extensions::registerLayout('hfb-canvas', CMS_TEST_ROOT . '/public/plugins/HeaderFooterBuilder/layouts/canvas');
    expect(new PageRenderer($theme, Runtime::page($repository->template($id)), true)->render())
        ->toContain('/themes/IntegrationParent/css/style.css', '/themes/Demo/js/theme.js');
});

it('supports independent preview themes without leaking their asset context', function () {
    [, $settings] = childThemeFixture();
    new DevflowPageBuilder($settings);
    $theme = new VihzhuoTheme($settings['theme'], 'Demo', previewTheme: 'Theme\\Demo\\DemoTheme');
    expect(Runtime::canvasAssets($theme))->toContain('/themes/Demo/css/style.css');
    expect(ThemeContext::current())->toBeNull()
        ->and(phpb_theme_asset('css/style.css'))->toContain('/themes/IntegrationParent/css/style.css');
});

it('lets children override parent integration filters without affecting other previews', function () {
    [, $settings] = childThemeFixture();
    $child = new DevflowPageBuilder($settings)->getTheme();
    $parentPreview = new VihzhuoTheme($settings['theme'], 'Demo', previewTheme: 'Theme\\Demo\\DemoTheme');
    expect(Runtime::slot('header', $child))->toBe('header');
    $slots = static fn (array $slots, $theme): array => $theme === $child
        ? array_replace($slots, ['header' => ['child-navigation']]) : $slots;
    $assets = static fn (array $assets, $theme): array => $theme === $child
        ? ['styles' => ['css/child.css'], 'scripts' => []] : $assets;
    Filter::getInstance()->addFilter('header.footer.slots', $slots, priority: 20, arguments: 2);
    Filter::getInstance()->addFilter('header.footer.canvas.assets', $assets, priority: 20, arguments: 2);
    try {
        expect(Runtime::slot('header', $child))->toBeNull()
            ->and(Runtime::slot('child-navigation', $child))->toBe('header')
            ->and(Runtime::slot('header', $parentPreview))->toBe('header');
        expect(Runtime::canvasAssets($child))->toContain('/themes/IntegrationChild/css/child.css')
            ->not->toContain('css/style.css', 'js/theme.js');
        expect(Runtime::canvasAssets($parentPreview))->toContain('/themes/Demo/css/style.css')
            ->not->toContain('css/child.css');
    } finally {
        Filter::getInstance()->removeFilter('header.footer.slots', $slots, priority: 20);
        Filter::getInstance()->removeFilter('header.footer.canvas.assets', $assets, priority: 20);
    }
});

it('uses the configured fallback and preserves explicitly configured custom adapters', function () {
    [, $settings, , $directory] = childThemeFixture();
    $this->selectedChildTheme = '';
    expect(new DevflowPageBuilder($settings)->getTheme()->getFolder())->toBe($directory . '/Demo');
    $settings['theme']['class'] = HfbStandaloneTheme::class;
    expect(new DevflowPageBuilder($settings)->getTheme())->toBeInstanceOf(HfbStandaloneTheme::class);
});

it('loads the bundled DemoChild example and its installed parent resources', function () {
    hfbFixture();
    $theme = new VihzhuoTheme([
        'folder' => CMS_TEST_ROOT . '/public/themes', 'folder_url' => '/themes',
    ], 'Demo', previewTheme: Theme\DemoChild\DemoChildTheme::class);
    expect(array_keys($theme->getThemeFolders()))->toBe(['DemoChild', 'Demo'])
        ->and($theme->getThemeBlocks()['header']->get('title'))->toBe('Child Header')
        ->and($theme->getThemeBlocks()['main']->getFolder())->toBe(CMS_TEST_ROOT . '/public/themes/Demo/blocks/main')
        ->and($theme->getThemeLayouts()['master']->getViewFile())->toBe(CMS_TEST_ROOT . '/public/themes/Demo/layouts/master/view.php')
        ->and($theme->getAssetPath('css/style.css'))->toBe('/themes/Demo/css/style.css');
});

it('retains parent PHP namespaces and replaces complete dynamic blocks', function () {
    [, $settings, , $directory] = childThemeFixture();
    new DevflowPageBuilder($settings);
    $theme = Runtime::theme();
    $folder = $directory . '/IntegrationParent/blocks/dynamic';
    mkdir($folder, 0700, true);
    file_put_contents($folder . '/config.php', '<?php return ["namespace" => "Theme\\\\IntegrationParent\\\\Blocks\\\\Dynamic"];');
    file_put_contents($folder . '/model.php', '<?php namespace Theme\\IntegrationParent\\Blocks\\Dynamic;
        class Model extends \\Vihzhuo\\Modules\\GrapesJS\\Block\\BaseModel {
            public function message(): string { return "Parent dynamic model"; }
        }');
    file_put_contents($folder . '/controller.php', '<?php namespace Theme\\IntegrationParent\\Blocks\\Dynamic;
        class Controller extends \\Vihzhuo\\Modules\\GrapesJS\\Block\\BaseController {}');
    file_put_contents($folder . '/view.php', '<span><?= phpb_e($block->message()) ?></span>');
    $block = $theme->getThemeBlocks()['dynamic'];
    expect($block->getModelClass())->toBe('Theme\\IntegrationParent\\Blocks\\Dynamic\\Model')
        ->and($block->getControllerClass())->toBe('Theme\\IntegrationParent\\Blocks\\Dynamic\\Controller');
    $page = new Page();
    $page->setData(['id' => 'dynamic-page', 'data' => ['html' => [], 'css' => '', 'blocks' => []]]);
    expect(new PageRenderer($theme, $page)->renderBlock('dynamic'))->toContain('Parent dynamic model');
    $childFolder = $directory . '/IntegrationChild/blocks/dynamic';
    mkdir($childFolder, 0700, true);
    file_put_contents($childFolder . '/view.html', '<span>Complete child replacement</span>');
    expect($theme->getThemeBlocks()['dynamic']->getModelFile())->toBeNull()
        ->and(new PageRenderer($theme, $page)->renderBlock('dynamic'))->toContain('Complete child replacement');
});

it('requires a complete child layout and rejects traversal and escaped symlinks', function () {
    [, $settings, , $directory] = childThemeFixture();
    $theme = new VihzhuoTheme($settings['theme'], 'Demo');
    mkdir($directory . '/IntegrationChild/layouts/master', 0700, true);
    file_put_contents($directory . '/IntegrationChild/layouts/master/config.php', '<?php return ["title" => "Child layout"];');
    $layoutPath = new ThemeLayout($theme, 'master')->getViewFile();
    expect($layoutPath)->toBe($directory . '/IntegrationChild/layouts/master/view.php')
        ->and(is_file($layoutPath))->toBeFalse()
        ->and(fn () => $theme->getAssetPath('../secret'))->toThrow(InvalidArgumentException::class)
        ->and(fn () => $theme->getAssetPath('%2e%2e/secret'))->toThrow(InvalidArgumentException::class)
        ->and(fn () => new VihzhuoTheme($settings['theme'], 'Demo', previewTheme: '../secret'))->toThrow(InvalidArgumentException::class);
    $outside = $directory . '/secret.css';
    file_put_contents($outside, 'private');
    symlink($outside, $directory . '/IntegrationChild/public/css/escape.css');
    expect(fn () => $theme->getAssetPath('css/escape.css'))->toThrow(RuntimeException::class);
    unlink($directory . '/IntegrationChild/public/css/escape.css');
    rename($directory . '/IntegrationParent', $directory . '/MissingParent');
    expect(fn () => $theme->getThemeFolders())->toThrow(RuntimeException::class);
});
