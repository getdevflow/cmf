<?php

declare(strict_types=1);

use App\Infrastructure\Http\Middlewares\CmsCsrfMiddleware;
use App\Infrastructure\Http\Middlewares\RestApiMiddleware;
use App\Infrastructure\Providers\AppServiceProvider;
use App\Infrastructure\Services\Options;
use Codefy\Framework\Http\Middleware\BindRequestMiddleware;
use Codefy\Framework\Http\Middleware\CorsMiddleware;
use Codefy\Framework\Http\Middleware\Csrf\CsrfProtectionMiddleware;
use Codefy\Framework\Http\Middleware\Csrf\CsrfTokenMiddleware;
use Codefy\Framework\Http\Middleware\Csrf\TokenMismatchException;
use Codefy\Framework\Http\RequestContext;
use Codefy\Framework\Scheduler\Mutex\Locker;
use Codefy\Framework\Scheduler\Mutex\FileLocker;
use Laminas\Diactoros\ResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\SimpleCache\CacheInterface;
use Qubus\Expressive\Database;
use Qubus\Http\Cookies\Factory\CookieFactory;
use Qubus\Http\Response;
use Qubus\Http\ServerRequest;
use Qubus\Routing\Route\RouteCollector;
use Qubus\Routing\Route\RouteParams;
use Qubus\Routing\Router;
use Qubus\View\Native\NativeLoader;
use Relay\Relay;

use function Codefy\Framework\Helpers\app;
use function Codefy\Framework\Helpers\config;

// Exercise the real matcher without dispatching controllers against the installation.
final class UpgradeRouteProbe extends Router
{
    protected function handle(
        object $route,
        ServerRequestInterface $serverRequest,
        RouteParams $params
    ): ResponseInterface {
        return new Response(headers: ['X-Action' => $route->getActionName()]);
    }
}

function upgradeRouter(): UpgradeRouteProbe
{
    $router = new UpgradeRouteProbe(new RouteCollector(), app(), new ResponseFactory());
    $router->setDefaultNamespace('App\\Infrastructure\\Http\\Controllers');
    foreach (['web/admin', 'api/v1', 'api/v2', 'web/web'] as $routes) {
        (require CMS_TEST_ROOT . '/routes/' . $routes . '.php')($router);
    }
    return $router;
}

function upgradeHandler(): RequestHandlerInterface
{
    return new class implements RequestHandlerInterface {
        public function handle(ServerRequestInterface $request): ResponseInterface
        {
            return new Response(status: 204);
        }
    };
}

function upgradeApiMiddleware(Database $database, CacheInterface $cache, string $key): RestApiMiddleware
{
    $cache->method('get')->willReturn($key);
    return new RestApiMiddleware(new Options($database, $cache));
}

it('routes all administrative mutations only to POST handlers', function (string $path, string $action) {
    $router = upgradeRouter();
    $post = $router->match(new ServerRequest(uri: 'https://cms.test' . $path, method: 'POST'));
    expect($post->getHeaderLine('X-Action'))->toEndWith('@' . $action);
    $get = $router->match(new ServerRequest(uri: 'https://cms.test' . $path, method: 'GET'));
    expect($get->getHeaderLine('X-Action'))->not->toEndWith('@' . $action);
})->with([
    ['/admin/auth/', 'auth'],
    ['/admin/logout/', 'logout'],
    ['/admin/flush-cache/', 'flushCache'],
    ['/admin/connector/', 'connector'],
    ['/admin/user/01ARZ3NDEKTSV4RRFFQ69G5FAV/d/', 'userDelete'],
    ['/admin/user/01ARZ3NDEKTSV4RRFFQ69G5FAV/switch-to/', 'userSwitchTo'],
    ['/admin/user/01ARZ3NDEKTSV4RRFFQ69G5FAV/switch-back/', 'userSwitchBack'],
    ['/admin/user/01ARZ3NDEKTSV4RRFFQ69G5FAV/reset-password/', 'userResetPassword'],
    ['/admin/product/01ARZ3NDEKTSV4RRFFQ69G5FAV/d/', 'productDelete'],
    ['/admin/product/01ARZ3NDEKTSV4RRFFQ69G5FAV/remove-featured-image/', 'removeFeaturedImage'],
    ['/admin/content-type/post/01ARZ3NDEKTSV4RRFFQ69G5FAV/d/', 'contentDelete'],
    ['/admin/site/01ARZ3NDEKTSV4RRFFQ69G5FAV/d/', 'siteDelete'],
    ['/admin/manager/1/d/', 'destroy'],
]);

it('routes recovery confirmation and the editor before public pages', function () {
    $router = upgradeRouter();
    foreach (['GET' => 'resetPasswordView', 'POST' => 'resetPasswordChange'] as $method => $action) {
        $response = $router->match(
            new ServerRequest(uri: 'https://cms.test/' . config('auth.password_reset_route'), method: $method)
        );
        expect($response->getHeaderLine('X-Action'))->toEndWith('@' . $action);
    }
    $response = $router->match(new ServerRequest(uri: 'https://cms.test/admin/manager/pagebuilder/', method: 'POST'));
    expect($response->getHeaderLine('X-Action'))->toEndWith('@websiteManager');
    foreach ($router->routes as $route) {
        expect($route->getActionName())->not->toContain('CronController');
    }
});

it('rejects malformed user identifiers', function () {
    $router = upgradeRouter();
    $response = $router->match(new ServerRequest(uri: 'https://cms.test/admin/user/A/d/', method: 'POST'));
    expect($response->getStatusCode())->toBe(404);
});

it('throttles login and both password recovery submissions before authentication', function () {
    $router = upgradeRouter();
    $count = 0;
    foreach ($router->routes as $route) {
        if (str_ends_with($route->getActionName(), '@auth') || str_ends_with($route->getActionName(), '@resetPasswordChange')) {
            expect($route->getMiddlewares()[0])->toBe('rate.limiter');
            $count++;
        }
    }
    expect($count)->toBe(3);
});

it('prepares a cookie and form token, accepts submission and rejects missing tokens', function () {
    $cookies = new CookieFactory(config());
    $prepare = new CsrfTokenMiddleware(config(), $cookies);
    $token = '';
    $pipeline = new Relay([$prepare, new BindRequestMiddleware(), function ($request) use (&$token) {
        $token = RequestContext::get()->getAttribute(CsrfTokenMiddleware::CSRF_SESSION_ATTRIBUTE);
        expect(csrf_field())->toContain($token);
        return new Response();
    }]);
    $response = $pipeline->handle(new ServerRequest(uri: 'https://cms.test/admin/login/', method: 'GET'));
    preg_match('/CSRFSESSID=([^;]+)/', $response->getHeaderLine('Set-Cookie'), $match);
    expect($token)->toMatch('/^[a-f0-9]{64}$/')->and(RequestContext::has())->toBeFalse();
    $request = new ServerRequest(uri: 'https://cms.test/admin/logout/', method: 'POST', cookies: ['CSRFSESSID' => rawurldecode($match[1])]);
    $pipeline = new Relay([$prepare, new CsrfProtectionMiddleware(config(), $cookies), fn () => new Response(status: 204)]);
    expect($pipeline->handle($request->withParsedBody(['_token' => $token]))->getStatusCode())->toBe(204);
    expect(fn () => $pipeline->handle($request))->toThrow(TokenMismatchException::class);
});

it('authenticates API keys only from a nonempty Bearer credential', function (string $key, string $header, array $query, int $status) {
    $api = upgradeApiMiddleware($this->createStub(Database::class), $this->createStub(CacheInterface::class), $key);
    $request = new ServerRequest(uri: 'https://cms.test/v2/content/', headers: ['Authorization' => $header], queryParams: $query);
    expect($api->process($request, upgradeHandler())->getStatusCode())->toBe($status);
})->with([
    ['secret-key', 'Bearer secret-key', [], 204],
    ['secret-key', 'bearer secret-key', [], 204],
    ['secret-key', '', ['key' => 'secret-key'], 401],
    ['', 'Bearer ', [], 401],
    ['secret-key', 'Bearer wrong', [], 401],
    ['secret-key', 'Bearer secret-key extra', [], 401],
]);

it('allows stateless API mutations only after validating the credential', function () {
    $csrf = new CsrfProtectionMiddleware(config(), new CookieFactory(config()));
    $middleware = new CmsCsrfMiddleware($csrf, upgradeApiMiddleware($this->createStub(Database::class), $this->createStub(CacheInterface::class), 'secret-key'));
    $request = new ServerRequest(uri: 'https://cms.test/v2/content/store/', method: 'POST');
    expect($middleware->process($request, upgradeHandler())->getStatusCode())->toBe(401);
    expect($middleware->process($request->withHeader('Authorization', 'Bearer secret-key'), upgradeHandler())->getStatusCode())->toBe(204);
});

it('handles CORS preflight before an authentication rejection', function () {
    $request = new ServerRequest(uri: 'https://cms.test/admin/auth/', method: 'OPTIONS', headers: [
        'Origin' => 'https://client.test', 'Access-Control-Request-Method' => 'POST',
        'Access-Control-Request-Headers' => 'X-CSRF-Token',
    ]);
    $response = new CorsMiddleware(config())->process($request, new class implements RequestHandlerInterface {
        public function handle(ServerRequestInterface $request): ResponseInterface
        {
            throw new RuntimeException('Preflight reached authentication.');
        }
    });
    expect($response->getStatusCode())->toBe(204);
});

it('renders an escaped CSRF header and token for AJAX requests', function () {
    RequestContext::set(new ServerRequest()->withAttribute('CSRF_TOKEN', '</script><script>alert(1)</script>'));
    try {
        $view = new NativeLoader(['framework' => CMS_TEST_ROOT . '/resources/views'], extension: 'phtml');
        $html = $view->render('framework::backend/partial/csrf');
        expect($html)->not->toContain('</script><script>alert')->toContain('window.location.origin');
    } finally {
        RequestContext::clear();
    }
});

it('registers serializable core notification jobs and a scheduler lock', function () {
    new AppServiceProvider(app())->register();
    expect(config()->array('queue.jobs'))->toHaveCount(4);
    foreach (config()->array('queue.jobs') as $job) {
        expect(is_subclass_of($job, Codefy\Framework\Queue\SerializableJob::class))->toBeTrue();
    }
    expect(app()->make(Locker::class))->toBeInstanceOf(FileLocker::class);
});

it('constructs custom SEO schema using the v3 API', function () {
    $schema = new Plugin\SimpleSeo\Service\SchemaFactoryService()->make([], [
        'schema_type' => 'Article', 'schema_json' => '{"@type":"Article","headline":"Upgrade"}',
    ], 'content');
    expect((string) $schema)->toContain('Article')->toContain('Upgrade');
});

it('keeps browser routes protected even when a valid API credential is supplied', function () {
    $csrf = new CsrfProtectionMiddleware(config(), new CookieFactory(config()));
    $middleware = new CmsCsrfMiddleware($csrf, upgradeApiMiddleware(
        $this->createStub(Database::class),
        $this->createStub(CacheInterface::class),
        'secret-key'
    ));
    $request = new ServerRequest(uri: 'https://cms.test/admin/logout/', method: 'POST', headers: [
        'Authorization' => 'Bearer secret-key',
    ])->withAttribute('CSRF_TOKEN', str_repeat('a', 64));
    expect(fn () => $middleware->process($request, upgradeHandler()))->toThrow(TokenMismatchException::class);
});

it('only exempts signature callbacks registered by their plugin', function (string $gateway) {
    $csrf = new CsrfProtectionMiddleware(config(), new CookieFactory(config()));
    $middleware = new CmsCsrfMiddleware($csrf, upgradeApiMiddleware(
        $this->createStub(Database::class),
        $this->createStub(CacheInterface::class),
        'secret-key'
    ));
    $request = new ServerRequest(uri: "https://cms.test/devcart/webhook/{$gateway}/", method: 'POST')
        ->withAttribute('CSRF_TOKEN', str_repeat('a', 64));
    $named = $request->withAttribute(Qubus\Routing\Route\RouteAttributes::NAME, 'devcart.webhook.' . $gateway);

    // A route name and URL alone must not exempt an inactive plugin's endpoint.
    expect(fn () => $middleware->process($named, upgradeHandler()))->toThrow(TokenMismatchException::class);

    $filter = Qubus\EventDispatcher\ActionFilter\Filter::getInstance();
    $callback = [Plugin\DevCart\DevCartPlugin::class, 'csrfSignatureRoutes'];
    $filter->addFilter('cms.csrf.signature_routes', $callback);
    try {
        expect($middleware->process($named, upgradeHandler())->getStatusCode())->toBe(204);
        expect(fn () => $middleware->process($request, upgradeHandler()))->toThrow(TokenMismatchException::class);
        expect(fn () => $middleware->process($named->withMethod('PUT'), upgradeHandler()))
            ->toThrow(TokenMismatchException::class);
        expect(fn () => $middleware->process($named->withUri(new Laminas\Diactoros\Uri('https://cms.test/admin/logout/')), upgradeHandler()))
            ->toThrow(TokenMismatchException::class);
        expect(Plugin\DevCart\DevCartPlugin::csrfSignatureRoutes(['another.webhook' => '/another/webhook']))
            ->toHaveKey('another.webhook', '/another/webhook');
    } finally {
        $filter->removeFilter('cms.csrf.signature_routes', $callback);
    }

    expect(fn () => $middleware->process($named, upgradeHandler()))->toThrow(TokenMismatchException::class);
})->with(['stripe', 'square']);

it('retires arbitrary table access without dispatching the legacy controller', function () {
    $router = new Router(new RouteCollector(), app(), new ResponseFactory());
    (require CMS_TEST_ROOT . '/routes/api/v1.php')($router);
    $response = $router->match(new ServerRequest(uri: 'https://cms.test/v1/user/', method: 'GET'));
    expect($response->getStatusCode())->toBe(410);
});

it('protects every supplied application POST form and avoids nested forms', function () {
    $files = new AppendIterator();
    foreach (['/resources/views/backend', '/public/plugins'] as $directory) {
        $files->append(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(CMS_TEST_ROOT . $directory)));
    }
    foreach ($files as $file) {
        if ($file->getExtension() !== 'phtml' || str_contains($file->getPathname(), '/vendor/')) {
            continue;
        }
        $template = file_get_contents($file->getPathname());
        $html = preg_replace('/<\?[\s\S]*?\?>/', '', $template);
        preg_match_all('/<\/?form\b[^>]*>/i', $html, $tags);
        $depth = 0;
        foreach ($tags[0] as $tag) {
            $depth += str_starts_with($tag, '</') ? -1 : 1;
            expect($depth, $file->getPathname())->toBeLessThanOrEqual(1)->toBeGreaterThanOrEqual(0);
        }
        preg_match_all('/<form\b(?:<\?[\s\S]*?\?>|[^>])*method="post"(?:<\?[\s\S]*?\?>|[^>])*>(.*?)<\/form>/si', $template, $forms);
        foreach ($forms[1] as $body) {
            expect($body, $file->getPathname())->toContain('csrf_field()');
        }
    }
});

it('binds configured middleware aliases for the PSR router', function () {
    new Application\Provider\MiddlewareAliasServiceProvider(app())->boot();
    foreach (config()->array('app.middlewares') as $alias => $class) {
        expect(app()->has($alias))->toBeTrue();
    }
    $resolver = new Qubus\Routing\Route\InjectorMiddlewareResolver(app());
    expect($resolver->resolve('cors'))->toBeInstanceOf(CorsMiddleware::class);
});

it('provisions new environment secrets once and preserves installed secrets', function () {
    $root = app()->basePath() . '/setup-fixture';
    mkdir($root . '/bootstrap', 0700, true);
    mkdir($root . '/storage', 0700, true);
    copy(CMS_TEST_ROOT . '/bootstrap/setup-environment.php', $root . '/bootstrap/setup-environment.php');
    file_put_contents($root . '/.env.example', "APP_BASE_PATH=\nAPP_KEY=\nAPP_SALT=\n");
    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($root . '/bootstrap/setup-environment.php');
    exec($command, $output, $status);
    expect($status)->toBe(0);
    $first = file_get_contents($root . '/.env');
    expect($first)->toMatch('/APP_SALT=[a-f0-9]{64}/')->toMatch('/APP_KEY=[a-f0-9]{64}/');
    file_put_contents($root . '/.enc.key', 'existing-key');
    exec($command, $output, $status);
    expect(file_get_contents($root . '/.env'))->toBe($first);
    expect(file_get_contents($root . '/.enc.key'))->toBe('existing-key');
    file_put_contents($root . '/storage/install.lock', 'installed');
    file_put_contents($root . '/.env', "APP_BASE_PATH=\"{$root}\"\nAPP_SALT=\n");
    exec($command, $output, $status);
    expect(file_get_contents($root . '/.env'))->toContain("APP_SALT=\n");
});
