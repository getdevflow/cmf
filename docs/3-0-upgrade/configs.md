# Devflow CMS 3.x Configuration Updates

## Site logo in Settings

Settings now includes a Site Logo picker in its right column. It uses the existing
image-only elFinder picker and saves the native `site_logo` option. Select or remove
the logo, then click Update. [Header Footer Builder](https://getdevflow.com/extensions/getdevflow/header-footer-builder) is optional.

Ship the Settings template and `settings-logo.js` with Core's updated
`AdminOptionsController` save allowlist and URL-aware `Options` reader. Themes can
read the option using `get_option('site_logo', '')` and escape it with `esc_url()`
when using it in an image's `src` attribute. No database migration is required.

## app.php

```php
'url' => env(key: 'APP_BASE_URL', default: 'http://localhost:8080/'),

'crypto_key' => is_file(__DIR__ . '/../.enc.key') ? trim(file_get_contents(__DIR__ . '/../.enc.key')) : '',

'middlewares' => Middleware::defaultMiddlewares()->merge([
    'csrf.protection' => \App\Infrastructure\Http\Middlewares\CmsCsrfMiddleware::class,
    'rest.api' => \App\Infrastructure\Http\Middlewares\RestApiMiddleware::class,
])->toArray(),

'base_middlewares' => array_merge([
    'cors',
    'http.exception',
    'security.headers',
    'http.cache.prevention',
    'referrer.spam',
    'firewall',
    'csrf.token',
    'csrf.protection',

    'user.cookie.decrypt',
    'bind.request',
], env(key: 'APP_DEBUG', default: false) ? ['php.debugbar'] : []),

commands => [
    App\Application\Console\Commands\MasterCronCommand::class,
    ///
]
```

## auth.php

```php
'encryption_key' => is_file(__DIR__ . '/../.enc.key') ? trim(file_get_contents(__DIR__ . '/../.enc.key')) : '',

'password_reset_route' => 'admin/password/reset/',

'password_reset_lifetime' => 3600,

'login_route' => env(key: 'AUTH_LOGIN_ROUTE', default: 'login'),

'login_url' => sprintf(
    rtrim((string) env(key: 'APP_BASE_URL'), '/') . '/admin/%s/',
    env(key: 'AUTH_LOGIN_ROUTE', default: 'login')
),

'admin_url' => rtrim((string) env(key: 'APP_BASE_URL'), '/') . '/admin/',

'redirect_guests_to' => sprintf(
    '/admin/%s/',
    env(key: 'AUTH_LOGIN_ROUTE', default: 'login')
),
```

## cms.php

```php
/*
|--------------------------------------------------------------------------
| Maintenance mode attributes.
|--------------------------------------------------------------------------
*/
'maintenance_mode_attrs' => [
    'retry_after' => '120',
    'cache_control' => 'no-cache, no-store, must-revalidate',
    'pragma' => 'no-cache',
    'expires' => '0',
],
```

## cookies.php

```php
use function Codefy\Framework\Helpers\env;

return [
    'path' => env(key: 'COOKIE_PATH', default: '/'),
    'domain' => env(key: 'COOKIE_DOMAIN', default: ''),
    // Authentication envelopes require positive lifetimes (seconds).
    'lifetime' => 86400,
    'remember' => 604800,
    'secure' => env(key: 'COOKIE_SECURE', default: true),
    'samesite' => env(key: 'COOKIE_SAMESITE', default: 'lax'),
    'crypt' => 'sha256',
    'secret_key' => env(key: 'APP_SALT'),
];
```

## cors.php

- added `RequestMethod::PATCH,`
- set `access-control-allow-credentials` to `false`, not an array

## csrf.php

- set `request_header` to `false`

## database.php

```php
'options' => [
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_PERSISTENT => env(key: 'DB_PERSISTENT'),
] + (extension_loaded('pdo_mysql') ? [
        \Pdo\Mysql::ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',
] : []),
```

## filesystem.php

The upgrade will replace your current filesystem config; it's likely this is one of the ones that are untouched by changes.

## firewall.php

```php
'exclusions' => [
    // Authorized editors intentionally submit HTML, including embedded media.
    // CSRF and vihzhuo:manage authorization still apply to this endpoint.
    [
        'path' => '/admin/manager/pagebuilder',
        'methods' => ['POST'],
        'rules' => ['xss'],
        'sources' => ['body'],
        'fields' => ['data'],
        'log' => false,
    ],
],

'ssrf' => [
    ...
    // Inspect submitted destinations, not this application's own host (e.g. localhost).
    'sources' => ['query', 'body'],
    ...
],
```

## mailer.php

The upgrade will replace your current mailer config since a new mailer library is being used.

## session.php

The upgrade will replace your current session config.

## throttle.php

The upgrade will replace your current throttle config.

## vihzhuo.php

The upgrade will replace your current vihzhuo config.

Core's Vihzhuo 2.1 integration supports child themes through PHP theme-class
inheritance. Keep `theme.class`, `folder`, and `folder_url`; the activated theme
takes precedence over `active_theme`. No parent map or additional cache settings
are needed. See [Vihzhuo child themes](vihzhuo-child-themes.md) for the bundled
example, complete resource overrides, assets, and safe developer previews.
