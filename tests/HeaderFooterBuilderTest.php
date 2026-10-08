<?php

declare(strict_types=1);

use Plugin\HeaderFooterBuilder\Controller\BuilderController;
use Plugin\HeaderFooterBuilder\Editor\TemplateEditor;
use Plugin\HeaderFooterBuilder\Editor\TemplateRenderer;
use Plugin\HeaderFooterBuilder\HeaderFooterBuilderPlugin;
use Plugin\HeaderFooterBuilder\Repository\BuilderRepository;
use Plugin\HeaderFooterBuilder\Support\Conditions;
use Plugin\HeaderFooterBuilder\Support\Html;
use Plugin\HeaderFooterBuilder\Support\Runtime;
use Qubus\EventDispatcher\ActionFilter\Filter;
use Qubus\Http\ServerRequest;
use Qubus\Routing\Psr7Router;
use Qubus\Routing\Route\RouteCollector;
use Qubus\Routing\Route\RouteParams;
use Qubus\Routing\Router;
use Qubus\Routing\TypeHintRequestResolver;
use Vihzhuo\Core\HttpContext;
use Vihzhuo\Extensions;
use Vihzhuo\Page;
use Vihzhuo\Theme;

use function Codefy\Framework\Helpers\app;
use function Codefy\Framework\Helpers\config;

require_once __DIR__ . '/Fixtures/Vihzhuo.php';

beforeEach(function () {
    $this->hfbOriginalConfig = config()->array('vihzhuo');
    hfbRegisterThemeFilters();
});

afterEach(function () {
    config()->setConfigKey('vihzhuo', $this->hfbOriginalConfig);
    hfbRemoveThemeFilters();
});

function hfbTemplate(string $id, array $conditions, string $kind = 'header', int $priority = 0): array
{
    return ['id' => $id, 'kind' => $kind, 'published' => true, 'priority' => $priority,
        'conditions' => Conditions::normalize($conditions)];
}

function hfbRegisteredRouter(): Router
{
    $container = clone app();
    $router = new Router(new RouteCollector(), $container, new Laminas\Diactoros\ResponseFactory());
    $container->singleton(Psr7Router::class, static fn () => $router);
    $previous = App\Application\Devflow::$PHP;
    App\Application\Devflow::$PHP = $container;
    try {
        // Route registration only needs the already booted Runtime repository.
        $plugin = new ReflectionClass(HeaderFooterBuilderPlugin::class)->newInstanceWithoutConstructor();
        $plugin->routes();
    } finally {
        App\Application\Devflow::$PHP = $previous;
    }
    return $router;
}

it('resolves requests for every registered builder route without instantiating an interface', function () {
    hfbFixture();
    $router = hfbRegisteredRouter();
    expect($router->routes)->toHaveCount(9);
    $request = new ServerRequest(
        uri: 'https://cms.test/admin/plugin/header-footer-builder/',
        method: 'POST',
        cookies: ['session' => 'test-session'],
        queryParams: ['template' => 'selected-template'],
        parsedBody: ['name' => 'Saved header']
    );
    $resolver = new TypeHintRequestResolver();
    $resolver->request = $request;
    foreach ($router->routes as $route) {
        $callback = Closure::fromCallable($route->getRouteAction()->getAction());
        $parameters = $resolver->getParameters(new ReflectionFunction($callback), [], []);
        expect($parameters[0]->getMethod())->toBe('POST')
            ->and($parameters[0]->getQueryParams())->toBe($request->getQueryParams())
            ->and($parameters[0]->getParsedBody())->toBe($request->getParsedBody())
            ->and($parameters[0]->getCookieParams())->toBe($request->getCookieParams());
    }

    // Invoke registered actions through Qubus's full parameter resolver chain.
    $response = $router->routes[1]->getRouteAction()->invoke($request, new RouteParams([]));
    expect($response->getStatusCode())->toBe(303)
        ->and(Runtime::repository()->templates()[0]['name'])->toBe('Saved header');
    $response = $router->routes[8]->getRouteAction()->invoke($request, new RouteParams(['id' => 'missing']));
    expect($response->getStatusCode())->toBe(404);
});

it('selects templates with exclusions, role restrictions, specificity, and explicit priority', function () {
    $templates = [hfbTemplate('global', ['global' => true]), hfbTemplate('type', ['content_types' => ['article']]),
        hfbTemplate('page', ['pages' => ['42'], 'exclude_roles' => ['guest']]),
        hfbTemplate('role', ['global' => true, 'roles' => ['member']])];
    $context = ['page_id' => '42', 'content_type' => 'article', 'roles' => ['member']];
    expect(Conditions::select($templates, 'header', $context)['id'])->toBe('page');
    expect(Conditions::select($templates, 'header', [...$context, 'roles' => ['guest']])['id'])->toBe('type');
    expect(Conditions::select($templates, 'header', ['roles' => ['member']])['id'])->toBe('role');
    $templates[] = hfbTemplate('priority', ['global' => true], priority: 20);
    expect(Conditions::select($templates, 'header', $context)['id'])->toBe('priority');
    expect(Conditions::select($templates, 'footer', $context))->toBeNull();
    expect(Conditions::score(Conditions::normalize(['global' => true, 'exclude_pages' => ['42']]), $context))->toBeNull();
    $templates = [hfbTemplate('draft', ['global' => true])];
    $templates[0]['published'] = false;
    expect(Conditions::select($templates, 'header', $context))->toBeNull();
});

it('creates, edits, and deletes templates without creating public Vihzhuo pages', function () {
    [$repository, , $pdo] = hfbFixture();
    $repository->migrate(); // activation is idempotent
    $id = $repository->saveTemplate(['name' => 'Global footer', 'kind' => 'footer', 'layout' => 'columns', 'conditions' => ['global' => 1]]);
    expect($repository->template($id)['published'])->toBeFalse();
    expect(json_encode($repository->template($id)['data']))->toContain('hfb-copyright');
    $repository->saveTemplate(['id' => $id, 'name' => 'Members footer', 'kind' => 'footer', 'published' => 1,
        'conditions' => ['global' => 1, 'roles' => ['member'], 'exclude_pages' => ['7']]]);
    expect($repository->template($id)['conditions']['roles'])->toBe(['member']);
    expect($pdo->query('SELECT COUNT(*) FROM test_pages')->fetchColumn())->toBe(0);
    expect(fn () => $repository->saveTemplate(['id' => $id, 'name' => 'Change kind', 'kind' => 'header']))->toThrow(InvalidArgumentException::class);
    $repository->deleteTemplate($id);
    expect($repository->template($id))->toBeNull();
});

it('round trips editor saves and rejects invalid data and GET mutations', function () {
    [$repository] = hfbFixture();
    $id = $repository->saveTemplate(['name' => 'Header', 'kind' => 'header']);
    $page = Runtime::page($repository->template($id));
    $editor = new TemplateEditor();
    expect($editor->getPageComponents($page)[0])->toContain('phpb-block', 'hfb-section', 'ID');
    $data = ['html' => ['<section class="custom">Saved header</section>'], 'css' => '.custom{--hfb-effects:1;--hfb-sticky:1}', 'blocks' => []];
    $request = new ServerRequest(uri: 'https://cms.test/admin/plugin/header-footer-builder/editor/', method: 'POST');
    $response = $editor->handleRequest($request->withParsedBody(['data' => json_encode($data)]), action: 'store', page: $page);
    expect($response->getStatusCode())->toBe(200);
    expect($repository->template($id)['data'])->toBe($data);
    expect($editor->handleRequest($request->withMethod('GET'), action: 'store', page: $page)->getStatusCode())->toBe(405);
    expect($editor->handleRequest($request->withParsedBody(['data' => 'invalid']), action: 'store', page: $page)->getStatusCode())->toBe(422);
    expect($repository->template($id)['data'])->toBe($data);
});

it('renders starter section placeholders into the editable Vihzhuo block map', function () {
    [$repository, $theme] = hfbFixture();
    $id = $repository->saveTemplate(['name' => 'Blank header', 'kind' => 'header']);
    $page = Runtime::page($repository->template($id));
    phpb_set_in_editmode();
    $blocks = new Vihzhuo\Modules\GrapesJS\PageRenderer($theme, $page, true)->getPageBlocksData();
    $sectionId = array_key_first($blocks['en']);
    expect($sectionId)->toStartWith('ID');
    expect($blocks['en'][$sectionId]['html'])->toContain('block-slug="hfb-section"', 'hfb-layout');
    expect(new TemplateEditor()->getPageComponents($page)[0])->toContain($sectionId);
    phpb_set_in_editmode(false);
});

it('replaces theme slots and preserves footer assets while leaving unmatched pages intact', function () {
    [$repository, $theme] = hfbFixture();
    $page = new Page();
    $page->setData(['id' => '42', 'data' => []]);
    $page->setTranslations([]);
    $id = $repository->saveTemplate(['name' => 'Footer', 'kind' => 'footer', 'published' => 1, 'conditions' => ['global' => 1, 'exclude_pages' => ['99']]]);
    $repository->saveData($id, ['html' => ['<section>Custom footer</section>'], 'css' => '', 'blocks' => []]);
    $renderer = new TemplateRenderer($theme, $page);
    $html = $renderer->renderBlock('footer');
    expect($html)->toContain('Custom footer', 'theme.js', '</body></html>')->not->toContain('Original footer');
    expect($renderer->renderBlock('header'))->toContain('Original header');
    $page->setData(['id' => '99', 'data' => []]);
    expect(new TemplateRenderer($theme, $page)->renderBlock('footer'))->toContain('Original footer');
});

it('leaves themes intact until filters explicitly opt them in', function () {
    [$repository, $theme] = hfbFixture();
    hfbRemoveThemeFilters();
    $id = $repository->saveTemplate(['name' => 'Global header', 'kind' => 'header', 'published' => 1,
        'conditions' => ['global' => 1]]);
    $repository->saveData($id, ['html' => ['<section>Custom header</section>'], 'css' => '', 'blocks' => []]);
    $page = new Page();
    $page->setData(['id' => '42', 'data' => []]);
    expect(new TemplateRenderer($theme, $page)->renderBlock('header'))->toContain('Original header')
        ->not->toContain('Custom header');
    expect(Runtime::slot('bb-navbar', $theme, $page))->toBeNull()
        ->and(Runtime::slot('bb-footer', $theme, $page))->toBeNull()
        ->and(Runtime::canvasAssets($theme))->not->toContain('css/style.css', 'bootstrap@', 'js/theme.js');
});

it('passes the actual rendering theme and page to replacement filters', function () {
    [, $theme] = hfbFixture();
    hfbRemoveThemeFilters();
    $page = new Page();
    $page->setData(['id' => '42', 'data' => []]);
    $filter = static function (array $slots, Vihzhuo\Contracts\ThemeContract $adapter, Vihzhuo\Contracts\PageContract $current) use ($theme, $page): array {
        expect($adapter)->toBe($theme)->and($current)->toBe($page);
        return ['header' => ['custom-nav'], 'mega' => ['unsupported']];
    };
    Filter::getInstance()->addFilter('header.footer.slots', $filter, arguments: 3);
    try {
        expect(Runtime::slot('custom-nav', $theme, $page))->toBe('header')
            ->and(Runtime::slot('unsupported', $theme, $page))->toBeNull();
    } finally {
        Filter::getInstance()->removeFilter('header.footer.slots', $filter);
    }
});

it('rejects unsafe preview asset URLs and tolerates malformed filter values', function () {
    [, $theme] = hfbFixture();
    hfbRemoveThemeFilters();
    $assets = static fn () => ['styles' => ['https://assets.example/style.css', 'javascript:alert(1)', [], '//outside.test/x.css'],
        'scripts' => ['js/theme.js', 'data:text/javascript,alert(1)', 'mailto:script.test', null]];
    $slots = static fn () => ['header' => 'header', 'footer' => [42]];
    Filter::getInstance()->addFilter('header.footer.canvas.assets', $assets);
    Filter::getInstance()->addFilter('header.footer.slots', $slots);
    try {
        expect(Runtime::canvasAssets($theme))->toContain('https://assets.example/style.css', '/themes/fixture/js/theme.js')
            ->not->toContain('javascript:', 'data:text', 'mailto:', 'outside.test');
        expect(Runtime::slot('header', $theme))->toBeNull()->and(Runtime::slot('42', $theme))->toBeNull();
    } finally {
        Filter::getInstance()->removeFilter('header.footer.canvas.assets', $assets);
        Filter::getInstance()->removeFilter('header.footer.slots', $slots);
    }
});

it('saves safe menu settings and only permits top-level mega menu assignments', function () {
    [$repository] = hfbFixture();
    $template = $repository->saveTemplate(['name' => 'Mega', 'kind' => 'mega']);
    $id = $repository->saveMenu(['name' => 'Main', 'items' => [[
        'label' => 'Explore', 'url' => 'javascript:alert(1)', 'template' => $template, 'width_mode' => 'full', 'width' => '70vw',
        'children' => [['label' => 'Child', 'url' => '/child', 'template' => $template]],
    ]]]);
    $item = $repository->menu($id)['items'][0];
    expect($item['url'])->toBe('#');
    expect($item['width'])->toBe('70vw');
    expect($item['children'][0]['template'])->toBe('');
    expect(Html::length('100px;background:red'))->toBe('320px');
    expect(Html::url('//evil.test'))->toBe('#');
    $repository->deleteMenu($id);
    expect($repository->menu($id))->toBeNull();
});

it('never serves drafts or role-restricted mega menus through the public AJAX endpoint', function () {
    [$repository] = hfbFixture();
    $id = $repository->saveTemplate(['name' => 'Private', 'kind' => 'mega']);
    $controller = new BuilderController($repository);
    $request = new ServerRequest(uri: 'https://cms.test/header-footer-builder/mega/' . $id);
    expect($controller->mega($request, $id)->getStatusCode())->toBe(404);
    $repository->saveTemplate(['id' => $id, 'name' => 'Private', 'kind' => 'mega', 'published' => 1, 'conditions' => ['roles' => ['admin']]]);
    expect($controller->mega($request, $id)->getStatusCode())->toBe(404);
    expect(Runtime::megaAvailable($repository->template($id), ['admin']))->toBeTrue();
    $repository->saveTemplate(['id' => $id, 'name' => 'Private', 'kind' => 'mega', 'published' => 1,
        'conditions' => ['roles' => ['admin'], 'exclude_roles' => ['admin']]]);
    expect(Runtime::megaAvailable($repository->template($id), ['admin']))->toBeFalse();
});

it('renders published AJAX templates without public HTTP caching and stops circular menu references', function () {
    [$repository] = hfbFixture();
    $id = $repository->saveTemplate(['name' => 'Public mega', 'kind' => 'mega', 'published' => 1]);
    $repository->saveData($id, ['html' => ['<div>Published content</div>'], 'css' => '', 'blocks' => []]);
    $controller = new BuilderController($repository);
    $request = new ServerRequest(uri: 'https://cms.test/header-footer-builder/mega/' . $id);
    $response = $controller->mega($request, $id);
    expect($response->getStatusCode())->toBe(200);
    expect($response->getHeaderLine('Cache-Control'))->toBe('private, no-store');
    expect(json_decode((string) $response->getBody(), true)['html'])->toContain('Published content');
    $menuId = $repository->saveMenu(['name' => 'Circular menu', 'items' => [['label' => 'Open', 'url' => '#', 'template' => $id]]]);
    Extensions::registerBlock('hfb-navigation', CMS_TEST_ROOT . '/public/plugins/HeaderFooterBuilder/blocks/navigation');
    $repository->saveData($id, ['html' => ['[block slug="hfb-navigation" id="IDnav0000000000000001"]'], 'css' => '',
        'blocks' => ['IDnav0000000000000001' => ['settings' => ['attributes' => ['menu' => $menuId]]]]]);
    expect($controller->mega($request, $id)->getStatusCode())->toBe(200);
});

it('resolves custom content types from published content routes', function () {
    [$repository, , $pdo] = hfbFixture();
    $pdo->exec("INSERT INTO test_content VALUES ('article','hello','published'),('news','draft','draft')");
    expect($repository->contentTypeForPath('/article/hello/'))->toBe('article');
    expect($repository->contentTypeForPath('/hello'))->toBe('article');
    expect($repository->contentTypeForPath('/news/draft'))->toBe('');
    expect($repository->contentTypeForPath('/other/hello'))->toBe('');
});
