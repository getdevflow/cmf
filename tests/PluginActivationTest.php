<?php

declare(strict_types=1);

use App\Infrastructure\Services\Options;
use App\Shared\Services\Registry;
use Plugin\HeaderFooterBuilder\HeaderFooterBuilderPlugin;
use Plugin\MenuBuilder\Controller\NavigationController;
use Plugin\MenuBuilder\MenuBuilderPlugin;
use Plugin\MenuBuilder\Repository\NavigationRepository;
use Plugin\SimpleSeo\SimpleSeoPlugin;
use Plugin\SocialShare\Support\SocialShareSchema;
use Psr\SimpleCache\CacheInterface;
use Qubus\Expressive\Connection\Pdo\Sqlite;
use Qubus\Expressive\QueryBuilder;
use Qubus\Expressive\Schema\Compiler;
use Qubus\Expressive\Schema\Compiler\SQLite as SQLiteCompiler;
use Qubus\Http\ServerRequest;
use Qubus\View\Renderer;

// Emulate a PDO statement whose first argument has a different parameter name.
final class ActivationStatement extends PDOStatement
{
    protected function __construct()
    {
    }

    public function bindValue(string|int $parameter, mixed $value, int $type = PDO::PARAM_STR): bool
    {
        return parent::bindValue($parameter, $value, $type);
    }
}

function activationDatabase(): QueryBuilder
{
    $connection = new class ([
        'dsn' => 'sqlite::memory:',
        'driver' => 'pdo_sqlite',
        'options' => [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_STATEMENT_CLASS => [ActivationStatement::class]],
    ]) extends Sqlite {
        public function schemaCompiler(): Compiler
        {
            // Exercise bound schema queries, as MySQL does when listing tables.
            return $this->schemaCompiler ??= new class ($this) extends SQLiteCompiler {
                public function getTables(string $database): array
                {
                    return ['sql' => 'SELECT name FROM sqlite_master WHERE type = ?', 'params' => ['table']];
                }
            };
        }
    };
    $database = new QueryBuilder($connection);
    $database->prefix = 'test_';
    return $database;
}

beforeEach(function () {
    $this->activationRegistry = Registry::getInstance()->getAll();
    Registry::getInstance()->set('tblPrefix', 'test_');
});

afterEach(function () {
    Registry::getInstance()->clear();
    Registry::getInstance()->add($this->activationRegistry);
});

it('binds positional and named values without depending on PDO parameter names', function () {
    $connection = activationDatabase()->getConnection();
    $row = $connection->query('SELECT ? AS number, ? AS flag, ? AS empty, ? AS text', [42, true, null, 'bound'])->fetchAssoc()->all()[0];
    expect($row)->toBe(['number' => 42, 'flag' => 1, 'empty' => null, 'text' => 'bound']);
    $row = $connection->query('SELECT :number AS number, :text AS text', ['number' => 7, ':text' => 'named'])->fetchAssoc()->all()[0];
    expect($row)->toBe(['number' => 7, 'text' => 'named']);
});

it('creates missing plugin tables and safely repeats migrations', function (string $pluginClass, array $tables) {
    $database = activationDatabase();
    $options = new Options($database, $this->createStub(CacheInterface::class));
    if ($pluginClass === SocialShareSchema::class) {
        $migrate = new SocialShareSchema($database)->migrateUp(...);
    } else {
        $plugin = new $pluginClass($options, $database, $this->createStub(Renderer::class));
        // SEO activation also writes settings; exercise its real schema migration independently.
        $migrate = $pluginClass === SimpleSeoPlugin::class
            ? static fn () => new ReflectionMethod($plugin, 'migrateUp')->invoke($plugin)
            : $plugin->onActivation(...);
    }
    expect($database->schema()->getTables())->toBe([]);
    $migrate();
    foreach ($tables as $table) {
        expect($database->schema()->hasTable('test_' . $table, true))->toBeTrue();
    }
    $before = $database->schema()->getTables(true);
    $migrate();
    expect($database->schema()->getTables(true))->toBe($before);
})->with([
    [HeaderFooterBuilderPlugin::class, ['hfb_template', 'hfb_menu']],
    [MenuBuilderPlugin::class, ['navigation_menu', 'navigation_item']],
    [SimpleSeoPlugin::class, ['seo_redirect', 'seo_404_log', 'seo_route', 'seo_submission_queue']],
    [SocialShareSchema::class, ['social_share_count']],
]);

it('returns a validation error for malformed reorder items without changing existing menu order', function () {
    $database = activationDatabase();
    $pdo = $database->getConnection()->pdo;
    $pdo->exec('CREATE TABLE test_page_translations (page_id TEXT, title TEXT, route TEXT)');
    $pdo->exec('CREATE TABLE test_product (product_id TEXT, product_title TEXT, product_slug TEXT)');
    $pdo->exec('CREATE TABLE test_content (content_id TEXT, content_title TEXT, content_slug TEXT)');
    $plugin = new MenuBuilderPlugin(new Options($database, $this->createStub(CacheInterface::class)), $database, $this->createStub(Renderer::class));
    $plugin->onActivation();
    $repository = new NavigationRepository($database);
    $menu = $repository->createMenu('Main');
    $first = $repository->addItem($menu, ['type' => 'custom', 'custom_label' => 'First']);
    $second = $repository->addItem($menu, ['type' => 'custom', 'custom_label' => 'Second']);
    $controller = new NavigationController($repository);
    $request = new ServerRequest(method: 'POST');
    foreach (['[{"position":0}]', '[{"id":"' . $first . '","position":9},{"position":0}]', '{"position":0}', 'invalid'] as $items) {
        $response = $controller->reorder($request->withParsedBody(['menu_id' => $menu, 'items' => $items]));
        expect($response->getStatusCode())->toBe(422)
            ->and(array_map(fn ($item) => $item->id(), $repository->tree($menu)))->toBe([$first, $second]);
    }
    $response = $controller->reorder($request->withParsedBody(['menu_id' => $menu, 'items' => json_encode([
        ['id' => $second, 'parent_id' => null, 'position' => 0],
        ['id' => $first, 'parent_id' => $second, 'position' => 0],
    ])]));
    expect($response->getStatusCode())->toBe(200)
        ->and($repository->tree($menu)[0]->id())->toBe($second)
        ->and($repository->tree($menu)[0]->children()[0]->id())->toBe($first);
});
