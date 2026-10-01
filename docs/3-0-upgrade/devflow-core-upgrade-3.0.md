# Upgrading Devflow Core to 3.0

Devflow Core 3.0 requires PHP 8.4+, Codefy 4, Vihzhuo 2, and Qubus Form 3.
This is a breaking release. This package contains the core services and controllers;
the installing CMS owns bootstrap, routes, configuration, and `framework::` templates.
Update those application files as part of deployment.

## Deployment

1. Back up the database, runtime files, and application encryption key. Stop queue workers
   and schedulers. Keep all nodes on the same dependency lock and cookie format.
2. Resolve the application's Composer dependencies and deploy its lock file. Do not replace
   an existing encryption key. Core notification payloads now also depend on that key.
3. Register `App\Infrastructure\Providers\AppServiceProvider` for console and HTTP execution.
   It adds the four built-in notification jobs to `queue.jobs`, preserving custom job entries,
   and binds the scheduler `Locker` to `FileLocker` under `storage/scheduler-locks`.
   Built-in cache warming and scheduled-content publication use `onlyOneInstance()`.
   Applications using a distributed locker must register their override after this provider.
4. Re-enqueue pending legacy jobs from trusted application data into a fresh Codefy 4 node.
   Legacy queue JSON cannot be safely restored. Account notification constructors no longer
   take passwords. Do not carry old plaintext password notifications into the new queue.
5. Apply the HTTP, mail, and template changes below, run the application integration tests,
   clear application caches, then restart workers. Users must sign in again.

The framework's PDO binding and the core database provider must use the same connection.
Content, product, and content-type aggregate repository constructors now require `Database`
as their final argument. Pass the same instance used by the event store and projections.
Event batches and their synchronous projections commit together; failures preserve pending
aggregate events. Do not wrap these repositories in a transactional pipeline: Codefy 4
rejects an already-active PDO transaction. External effects in projections are not rolled
back by a database transaction; defer those effects until after commit.

Event replay is ordered by playhead; `loadFromPlayhead()` includes every event at or above
its argument. Invalid JSON raises an exception instead of being stored as empty data.

## HTTP and authentication

- `AdminAuthController` and `AdminDashboardController` now inject `Qubus\Routing\Router`.
  Controllers no longer inherit that dependency from Codefy's `BaseController`.
- Login, logout, account creation/update/deletion, user switching, password recovery submissions,
  and page deletion require POST. Replace GET mutation links with CSRF-protected forms.
  GET logout returns 405; provide a confirmation page in the application if desired.
- Put CORS before authentication/firewall rejection, `csrf.token` before `csrf.protection`,
  and `bind.request` after middleware that attaches CSRF/authentication attributes but before
  route handlers and templates that read `RequestContext`. Authentication precedes
  session-cookie issuance. Protect all unsafe CMS/editor routes with CSRF middleware.
  Register the framework throttle middleware on login and recovery routes. Configure an
  appropriate limit; a client-provided forwarded IP must not become a trusted identity.
- The CMS retains its server-backed `USERCOOKIEID` and also uses Codefy's versioned,
  expiring authentication cookie. User switching writes the new format. Logout removes
  both browser cookies and revokes the CMS cookie record. Codefy cookie deletion alone
  does not invalidate a captured token; enforce token rotation where immediate revocation
  of all sessions is required.
- Profile validation derives identity, role, and status from the authenticated user.
  Posted values cannot change another account or elevate privileges. Administration uses
  the separately authorized user-update validator. Account creation no longer updates an
  existing account by email or a submitted ID.
- Both authentication paths upgrade weak password hashes with a conditional update matching
  the old hash. The authentication repository honors configured password and token columns.
- Set `APP_BASE_URL` to the full public URL. Configure secure, HttpOnly, SameSite cookies
  explicitly for deployment. Untrusted proxy headers do not establish HTTPS.
- Disable debug mode in production and register debug-bar middleware only in debug mode.
  The core debug-bar provider no longer registers its collectors in production.
- CMS controller redirects derived from Referer are restricted to local or same-origin URLs.

## Password recovery

Recovery now proves email ownership before changing a password. A request stores only a
SHA-256 digest and expiry in the existing `user_activation_key` column. A successful reset
atomically consumes that value, hashes the new password, and rotates the user token. Replayed,
expired, superseded, and wrong-user tokens are rejected. Account notification emails contain
no passwords. The low-level `reset_password()` helper remains an explicit administrative API;
HTTP recovery no longer calls it.

Add configuration to the application's existing `auth.php` array:

```php
'password_reset_route' => 'admin/password/reset/',
'password_reset_lifetime' => 3600,
'password_min_length' => 12,
```

Register GET at that path to `AdminAuthController::resetPasswordView` and POST at the same
path to `AdminAuthController::resetPasswordChange`. The existing recovery-request form can
continue POSTing `email` to `resetPasswordChange`. Register CSRF preparation/protection and
throttling on both POST routes, with request context binding on the GET route too. The token
confirmation form is rendered by core and includes `csrf_field()`, `Cache-Control: no-store`,
and `Referrer-Policy: no-referrer`. Ensure proxy/access logs redact recovery query tokens.

The recovery service uses `auth.pdo.table`, the CMS columns `user_id`, `user_login`,
`user_email`, and `user_activation_key`, and the configured password/token columns.
The activation-key column must allow NULL and at least 75 characters. Recovery shares this
column with activation; requesting a reset supersedes any existing activation credential.

## Page builder

Controllers now accept the PSR request and return Vihzhuo's response directly, preserving
status, headers, redirects, and streamed assets. Maintenance checks run before public-page
rendering. Unresolved pages return 404.

`DevflowPageBuilder` replaces upstream default auth, manager, and editor classes with the
CMS adapters, while retaining explicitly configured custom classes. Equivalent configuration:

```php
'auth' => [
    'use_login' => true,
    'class' => App\Infrastructure\Services\Vihzhuo\VihzhuoAuth::class,
],
'website_manager' => [
    'use_website_manager' => true,
    'class' => App\Infrastructure\Services\Vihzhuo\WebsiteManager::class,
],
'pagebuilder' => ['class' => App\Infrastructure\Services\Vihzhuo\PageEditor::class],
```

Merge these keys into your existing Vihzhuo configuration; retain its storage/theme/router settings.
The application-namespace auth adapter delegates to CMS authentication and permissions too.
The manager uses `framework::backend/admin/manager/index` and
`framework::backend/admin/manager/page-settings`. Every create/edit/delete/settings form in
those application templates must include `csrf_field()`. Escape page names in dialogs.

The editor adds the configured CSRF header to same-origin jQuery requests and GrapesJS uploads
using Vihzhuo's before-init event. Saves, uploads,
and asset deletion require POST. Keep `vihzhuo:manage` authorization and CSRF protection on
editor routes. If the firewall rejects edited HTML, scope its exclusion to the editor POST
`data` field at the exact configured path, retaining other rules. Put the public catch-all
route after account and administrative routes. Test the application templates in a browser.

## Mail, queues, validation, and storage

Configure `mailer.dsn` from `MAILER_DSN` (Symfony Mailer), and `MAILER_FROM_EMAIL` for the
sender. The notification classes use the default transport; old `MAILER_USERNAME` and
PHPMailer SMTP settings no longer select delivery. Direct delivery catches Symfony transport
exceptions. Use `null://null` in tests and a verified production DSN for real delivery.

Notifications implement `SerializableJob`, validate their fields, and encrypt the complete
payload with the application key before storage. Extra fields are discarded. Their retry,
lease, and schedule settings are class defaults; arbitrary runtime changes are not serialized.
Custom persistent jobs must implement the new contract and be explicitly allowlisted.
Store queue and scheduler files outside the public directory with private permissions.
Do not delete lock files while workers run. Local file locks are not a multi-host broker.

Codefy 4 returns only accepted validation fields. Core validators now include the normalized
creation/publication timestamps, currency, and locale needed downstream. Date-normalization
pipes must run before content/product validation. Optional DTO fields have explicit defaults.
Custom validators must explicitly declare every field used by a DTO or write operation.

## Verification

Run `composer test`, `composer cs-check`, `composer analyse`,
`composer validate --no-check-publish`, and `composer audit --locked`.
Migration verification on PHP 8.4 and PHP 8.5: 89 tests passed (266 assertions each).
All 444 PHP files passed PHP 8.4 syntax checks. The core tests cover cookie compatibility, queue payloads,
password recovery, password rehashing, method restrictions, and event-store rollback/replay.
Static analysis at level 0 checks the whole core for basic compatibility errors; this does
not claim higher-level type-analysis coverage.

Production SMTP, application routes/templates, multi-host storage, and live Swoole need
application-level verification. The dependency audit during migration reported no advisories.
