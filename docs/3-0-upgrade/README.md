# Devflow CMS 3.x application upgrade

This application targets Devflow Core 3, CodefyPHP 4, Vihzhuo 2, Qubus Form 3,
and PHP 8.4+. Application changes leave the installed `core/` package untouched.
See the [core upgrade guide](devflow-core-upgrade-3.0.md) and
[CodefyPHP guide](https://github.com/codefyphp/codefy/blob/4.x/docs/4-0-upgrade/upgrade-4.0.md) for the underlying contracts.

## Environment and Composer Changes

### .env

Make sure to add the following changes to your .env file based on your needs and configuration:

```dotenv
AUTH_LOGIN_ROUTE=login

## Cookies: enable secure cookies when deploying with HTTPS.
COOKIE_SECURE=true
COOKIE_DOMAIN=''
COOKIE_PATH=/
COOKIE_SAMESITE=lax

MAILER_DSN=smtp://localhost
MAILER_SMTP_DSN=smtp://localhost
MAILER_SENDMAIL_DSN=sendmail://default
MAILER_QMAIL_DSN="sendmail://default?command=/usr/sbin/qmail-inject"
MAILER_TRANSACTIONAL_DSN=postmark+api://KEY@default
MAILER_DEBUG=false
MAILER_EML_FILE=files/mail-debug.eml
MAILER_FROM_EMAIL=no-reply@example.com
MAILER_FROM_NAME="${APP_NAME}"
```

### composer.json

The most important change is switching out the `getdevflow/core` dependency from `2` to `3` Your `composer.json` file should look 
similar to below:

```json
{
    "name": "getdevflow/cmf",
    "type": "project",
    "description": "Developer-centric content management framework.",
    "keywords": ["framework", "content-management", "cms", "cmf", "content-management-system", "headless", "headless-cms"],
    "license": "GPL-2.0-only",
    "authors": [
        {
            "name": "Joshua Parker",
            "email": "joshua@joshuaparker.dev",
            "homepage": "https://joshuaparker.dev/",
            "role": "Developer"
        }
    ],
    "require": {
        "php": ">=8.4",
        "ext-curl": "*",
        "ext-mbstring": "*",
        "ext-pdo": "*",
        "ext-zip": "*",
        "composer/installers": "^2.3",
        "getdevflow/core": "3.x-dev",
        "oomphinc/composer-installers-extender": "^2.0"
    },
    "autoload": {
        "psr-4": {
            "Application\\": "Cms/Application",
            "Domain\\": "Cms/Domain",
            "Database\\Seeders\\": "database/seeders/",
            "Infrastructure\\": "Cms/Infrastructure",
            "Plugin\\": "public/plugins/",
            "Theme\\": "public/themes/"
        }
    },
    "scripts": {
        "devstan": "@analyse",
        "test": "vendor/bin/pest",
        "cs-check": "phpcs",
        "cs-fix": "phpcbf",
        "setup-environment": "@php bootstrap/setup-environment.php",
        "post-create-project-cmd": [
            "@setup-environment",
            "@php codex generate:key:file"
        ],
        "analyse": "phpstan analyse --no-progress"
    },
    "config": {
        "optimize-autoloader": true,
        "sort-packages": true,
        "allow-plugins": {
            "dealerdirect/phpcodesniffer-composer-installer": true,
            "pestphp/pest-plugin": true,
            "composer/installers": true,
            "oomphinc/composer-installers-extender": true
        }
    },
    "require-dev": {
        "qubus/qubus-coding-standard": "^2.1.2"
    },
    "extra": {
        "installer-types": ["devflow-core", "devflow-plugin", "devflow-theme"],
        "installer-paths": {
            "core/": ["type:devflow-core"],
            "public/plugins/{$name}/": ["type:devflow-plugin"],
            "public/themes/{$name}/": ["type:devflow-theme"]
        }
    }
}

```

## Deployment

1. Back up the database, runtime files, `.env`, and `.enc.key`. Stop workers and
   scheduled tasks before deploying. Preserve both the encryption key and
   `APP_SALT`: the salt contributes to existing site-directory identifiers.
   **An installed application with a missing salt needs its original value
   restored from backup. Do not generate a replacement as an upgrade step.**
2. Deploy the application with its `composer.lock` and resolved dependencies.
   `2.x-dev` core dependency should be replaced with `3.x-dev` for bleeding edge or `^3.0` for stable. Do not rerun project
   creation or `devflow:install` against an installed site.
3. Merge configuration. Use the full public `APP_BASE_URL`, including its scheme;
   retain the `APP_BASE_PATH` appropriate to the deployment (for example,
   `/var/www/html` inside a container). Configure `MAILER_DSN` and
   `MAILER_FROM_EMAIL`. Keep `APP_DEBUG=false` and `COOKIE_SECURE=true` on HTTPS
   production sites. Forwarded headers do not establish trusted HTTPS or client IPs.
4. Restore pending legacy jobs from trusted application data into a fresh queue
   node after stopping old workers. Never copy serialized legacy password-email
   payloads into the new queue. The core provider registers its four encrypted,
   serializable notification jobs and the scheduler's local file locker.
5. Run application checks, clear application caches using `php codex cache:clear`,
   and restart workers and the scheduler. Users must sign in again. Verify SMTP,
   plugin callbacks, page editing, uploads, and site switching on a staging site.

No database migration is required for the stock user activation-key column: it
is already nullable and 191 characters long. Custom schemas must support NULL
and at least 75 characters. No database migration or cache purge was executed
against the existing installation during this application upgrade.

## HTTP and template changes

- Login, recovery, logout, user switching, deletion, cache flushing, featured-image
  removal, and media connector requests use POST. GET logout returns 405 without
  expiring a session. Custom clients and themes must stop using mutation links.
- Every supplied application POST form carries `csrf_field()`. Bundled plugin
  POST templates also receive tokens. The admin layout supplies CSRF headers for
  same-origin unsafe jQuery requests, including workflow and notification actions.
  elFinder uses POST; the standalone picker also loads CSRF support.
- Page-manager forms use the application templates. Page names are escaped in
  deletion dialogs. The core editor supplies its own jQuery/GrapesJS integration.
- CORS precedes error handling, security filtering, and authentication. CSRF
  preparation precedes protection; request binding occurs after token and cookie
  middleware and again after login authentication. The application provider binds
  the final middleware alias map to the router's container.
- Login and both recovery POST endpoints share five attempts per ten minutes by
  connection address. GET login is not counted. Strict concurrent quotas still
  require an atomic limiter backend.
- Password-recovery request forms submit to `/admin/reset-password/`. Recovery
  links and confirmation submissions use `/admin/password/reset/`. Emails contain
  a recovery link, never a replacement password.
- The public catch-all accepts GET after administrative, API, theme, and plugin
  routes. ULID constraints now enforce the full 26-character format.
- Plugins own their signature-authenticated callback exceptions. The generic
  `cms.csrf.signature_routes` filter starts with an empty map of route names to
  exact paths (without trailing slashes). Only loaded plugins register entries;
  these callbacks must verify signatures before processing events.

## API breaking changes

The generic `/v1/{table}` API is retired with HTTP 410. It exposed arbitrary
database tables and used interpolated SQL identifiers. Use explicit `/v2/`
resource endpoints or add narrowly authorized application endpoints instead.

API clients must send `Authorization: Bearer <site-api-key>`. Query-string keys,
empty configured keys, and malformed Bearer values are rejected with 401.
The key is a privileged site credential; keep it on trusted servers. Explicit,
validated Bearer credentials allow `/v2/` mutations without browser CSRF tokens.
The same credential never exempts `/admin/` forms from CSRF checks.

## Scheduler, mail, and storage

The public `/admin/cron/master/` trigger is removed. `php codex cms:cron` runs the
same scheduled-product publication and per-site hooks through the console. The
scheduler invokes it every minute with `onlyOneInstance()`. Queue execution and
cleanup commands also use overlap locks. `Schedule::php('codex queue:run')` was
replaced by the supported console-command API.

Private disk file/directory modes are 0600/0700. Runtime queue, session, cookie,
mail, and scheduler files are ignored by Git. Existing permissions and legacy
queue files are not changed automatically. Multi-host workers require a shared
broker and distributed locks rather than local file locks.

For a new checkout, `composer setup-environment` fills a blank base path and
provisions missing `APP_KEY`/`APP_SALT` only before installation. It preserves
existing values, refuses an `.env` symlink, and restricts `.env` permissions.
The Composer project-creation script creates an encryption key only for a new
project; it is not an upgrade command.


