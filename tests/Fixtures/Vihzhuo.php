<?php

declare(strict_types=1);

use Plugin\HeaderFooterBuilder\Repository\BuilderRepository;
use Plugin\HeaderFooterBuilder\Support\Runtime;
use Qubus\EventDispatcher\ActionFilter\Filter;
use Qubus\Expressive\Connection\Pdo\Sqlite;
use Qubus\Expressive\QueryBuilder;
use Qubus\Http\ServerRequest;
use Vihzhuo\Core\DB;
use Vihzhuo\Core\HttpContext;
use Vihzhuo\Contracts\ThemeContract;
use Vihzhuo\Extensions;
use Vihzhuo\Page;
use Vihzhuo\Theme;

use function Codefy\Framework\Helpers\app;
use function Codefy\Framework\Helpers\config;

final class HfbStandaloneTheme extends Theme
{
}

function hfbFixtureSlots(array $slots, ThemeContract $theme): array
{
    return array_replace($slots, ['header' => ['header'], 'footer' => ['footer']]);
}

function hfbFixtureCanvasAssets(array $assets, ThemeContract $theme): array
{
    return ['styles' => basename($theme->getFolder()) === 'IntegrationChild'
        ? ['css/style.css', 'css/child.css'] : ['css/style.css'], 'scripts' => ['js/theme.js']];
}

function hfbRegisterThemeFilters(): void
{
    Filter::getInstance()->addFilter('header.footer.slots', 'hfbFixtureSlots', arguments: 2);
    Filter::getInstance()->addFilter('header.footer.canvas.assets', 'hfbFixtureCanvasAssets', arguments: 2);
}

function hfbRemoveThemeFilters(): void
{
    Filter::getInstance()->removeFilter('header.footer.slots', 'hfbFixtureSlots');
    Filter::getInstance()->removeFilter('header.footer.canvas.assets', 'hfbFixtureCanvasAssets');
}

function hfbFixture(): array
{
    $directory = app()->basePath() . '/hfb-' . bin2hex(random_bytes(5));
    mkdir($directory, 0700, true);
    $database = ['driver' => 'sqlite', 'dsn' => 'sqlite:' . $directory . '/test.sqlite', 'prefix' => 'test_'];
    $connection = new Sqlite([...$database, 'driver' => 'pdo_sqlite']);
    $db = new QueryBuilder($connection);
    $db->prefix = 'test_';
    $pdo = $connection->pdo;
    $pdo->exec('CREATE TABLE test_pages (id INTEGER PRIMARY KEY, name TEXT, data TEXT)');
    $pdo->exec('CREATE TABLE test_page_translations (id INTEGER PRIMARY KEY, page_id INTEGER, locale TEXT, title TEXT, route TEXT)');
    $pdo->exec('CREATE TABLE test_content_type (content_type_slug TEXT, content_type_title TEXT)');
    $pdo->exec('CREATE TABLE test_content (content_type TEXT, content_slug TEXT, content_status TEXT)');
    $pdo->exec('CREATE TABLE test_settings (id INTEGER PRIMARY KEY, setting TEXT, value TEXT, is_array INTEGER)');
    $pdo->exec('CREATE TABLE test_uploads (id INTEGER PRIMARY KEY, public_id TEXT, server_file TEXT, original_file TEXT, mime_type TEXT)');
    $repository = new BuilderRepository($db);
    $repository->migrate();
    Runtime::boot($repository);
    global $phpb_config, $phpb_db, $phpb_translations, $phpb_editmode;
    // This fixture tests the standalone renderer; Core adapter integration is covered separately.
    $phpb_config = [
        'general' => ['base_url' => 'https://cms.test', 'language' => 'en', 'assets_url' => '/phpb-assets'],
        'storage' => ['database' => $database], 'page' => ['class' => Page::class, 'table' => 'pages',
            'translation' => ['class' => Vihzhuo\PageTranslation::class, 'table' => 'page_translations', 'foreign_key' => 'page_id']],
        'theme' => ['class' => HfbStandaloneTheme::class, 'folder' => $directory, 'folder_url' => '/themes', 'active_theme' => 'fixture'],
        'setting' => ['class' => Vihzhuo\Setting::class], 'class_replacements' => [],
        'cache' => ['enabled' => false], 'pagebuilder' => ['url' => '/admin/plugin/header-footer-builder/editor/'],
        'auth' => ['use_login' => false], 'website_manager' => ['use_website_manager' => false],
        'router' => ['class' => Vihzhuo\Modules\Router\DatabasePageRouter::class, 'use_router' => false],
    ];
    $phpb_db = new DB($database);
    $phpb_translations = require CMS_TEST_ROOT . '/vendor/nomadicjosh/vihzhuo/lang/en.php';
    phpb_set_in_editmode(false);
    Extensions::registerBlock('hfb-section', CMS_TEST_ROOT . '/public/plugins/HeaderFooterBuilder/blocks/section');
    HttpContext::setRequest(new ServerRequest(uri: 'https://cms.test/'));
    foreach (['header' => '<nav>Original header</nav>', 'footer' => '<footer>Original footer</footer><script src="theme.js"></script></body></html>'] as $slug => $markup) {
        mkdir($directory . '/fixture/blocks/' . $slug, 0700, true);
        file_put_contents($directory . '/fixture/blocks/' . $slug . '/view.html', $markup);
    }
    config()->setConfigKey('vihzhuo', $phpb_config);
    return [$repository, new Theme($phpb_config['theme'], 'fixture'), $pdo, $directory];
}
