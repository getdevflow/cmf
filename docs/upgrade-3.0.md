# Devflow CMS 3.x application upgrade

This application targets Devflow Core 3, CodefyPHP 4, Vihzhuo 2, Qubus Form 3,
and PHP 8.4+. Application changes leave the installed `core/` package untouched.
See the [core upgrade guide](4-0-upgrade/devflow-core-upgrade-3.0.md) and
[CodefyPHP guide](https://github.com/codefyphp/codefy/blob/4.x/docs/4-0-upgrade/upgrade-4.0.md) for the underlying contracts.

## Deployment

1. Back up the database, runtime files, `.env`, and `.enc.key`. Stop workers and
   scheduled tasks before deploying. Preserve both the encryption key and
   `APP_SALT`: the salt contributes to existing site-directory identifiers.
   **An installed application with a missing salt needs its original value
   restored from backup. Do not generate a replacement as an upgrade step.**
2. Deploy the application with its `composer.lock` and resolved dependencies.
   `3.x-dev` and its framework development dependency should be replaced with
   stable constraints when those releases are available. Do not rerun project
   creation or `devflow:setup` against an installed site.
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
  elFinder uses POST; the standalone picker also loads CSRF support. AdminBar's
  fetch requests include the configured token header.
- Page-manager forms use the application templates. Page names are escaped in
  deletion dialogs. The core editor supplies its own jQuery/GrapesJS integration;
  the old standalone `editor-csrf.phtml` fragment is not loaded.
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
- CustomFields cloning/deletion requires POST. CustomFields, MenuBuilder, and
  SqliteAdmin route groups require `manage:plugins`, in addition to CSRF for writes.
- Plugins own their signature-authenticated callback exceptions. The generic
  `cms.csrf.signature_routes` filter starts with an empty map of route names to
  exact paths (without trailing slashes). Only loaded plugins register entries;
  these callbacks must verify signatures before processing events. DevCart registers
  its Stripe/Square callbacks through this hook. Exemption requires POST and a
  matching route name and path; the CMS middleware contains no DevCart-specific rules.

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

SimpleSeo uses SEO v3's named `Thing` constructor arguments. DevCart uses Symfony
mail transport exceptions and records confirmation delivery only after the mail
helper succeeds, allowing failures to be retried.

Private disk file/directory modes are 0600/0700. Runtime queue, session, cookie,
mail, and scheduler files are ignored by Git. Existing permissions and legacy
queue files are not changed automatically. Multi-host workers require a shared
broker and distributed locks rather than local file locks.

For a new checkout, `composer setup-environment` fills a blank base path and
provisions missing `APP_KEY`/`APP_SALT` only before installation. It preserves
existing values, refuses an `.env` symlink, and restricts `.env` permissions.
The Composer project-creation script creates an encryption key only for a new
project; it is not an upgrade command.

## Verification and limits

Verified on PHP 8.5.10:

- Application suite: 35 tests, 1,156 assertions. It covers real route matching,
  CSRF cookies/submissions and generated forms, CORS, Bearer authentication,
  signature-route exemptions, alias resolution, job registration, SEO schema,
  and repeatable environment setup. Tests use an isolated container and runtime
  directory; controller mutations are not dispatched against the installed database.
- DevCart suite: 16 tests, 42 assertions.
- Application coding standards and PHPStan level 0 pass. This is basic
  compatibility analysis, not a claim of strict type coverage across dependencies.
- 444 application and bundled-plugin PHP/template files passed syntax checks.
- CLI route and scheduler registration succeeded.
- Direct HTTP-pipeline smoke checks: login 200 with a CSRF form field, GET logout
  405, tokenless POST logout 412, retired API 410, and unauthenticated API write 401.
  Host-path and temporary salt overrides were used only in the smoke process.
- Composer manifest validates with the expected development-constraint warning.
  Audit returned no security advisories, but reported abandoned packages:
  `laminas/laminas-loader`, `marcusschwarz/lesserphp`, `meenie/javascript-packer`,
  and `oomphinc/composer-installers-extender`. Audit therefore exits nonzero.

Run `composer test`, `composer cs-check`, `composer analyse`,
`composer validate --no-check-publish`, and `composer audit --locked` on deployment.
In a sandbox that cannot open PHPStan's local worker socket, run
`composer analyse -- --debug` for serial analysis.

Interactive browser editing, production SMTP, external payment delivery,
PHP 8.4 runtime execution, and multi-host/Swoole behavior were not verified.
The current supplied environment needs its original `APP_SALT` restored before
normal HTTP login can render without a temporary test override.
