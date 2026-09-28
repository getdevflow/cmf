# Page Builder 4.0 release and migration

This guide describes the application changes in this pagebuilder. The other documents describe
`codefyphp/codefy` itself; their framework test counts are not pagebuilder test results.
PHP 8.4 remains supported. The framework dependency currently follows `4.x-dev` while 4.0
is being prepared; switch to `^4.0` once the stable framework is published and resolve a new lock.

## New installations

`composer create-project` copies `.env.example` only when `.env` is absent, writes the new
project's absolute path to `APP_BASE_PATH`, then runs `php codex generate:key:file`. Existing,
nonempty `APP_BASE_PATH` values are preserved. It no longer creates an empty key placeholder.
Key creation refuses an existing file or symlink. The key is private (`0600` on POSIX).

For a checkout, run `composer install`, then `composer setup-environment`. That command copies
`.env.example` when needed and fills a blank `APP_BASE_PATH`. Run `php codex generate:key:file`
only if no key exists. Preserve an existing deployment's key; do not rerun project creation
scripts as an upgrade or key-rotation mechanism.

The default SQLite DSN resolves to `database/codefy.sqlite` even with an empty `APP_BASE_PATH`.
Set `DB_DSN` explicitly for another database, then run `php codex migrate`. Use your own trusted
administrative provisioning process for the first administrator. Public registration creates
regular users. The development seeder uses known example passwords and refuses production.

## HTTP configuration and account boundaries

- Set `APP_BASE_URL` to the complete public URL, including `https://` behind TLS proxies.
  `config/app.php` now reads this same variable. Forwarded HTTPS/client-address headers are
  not automatically trusted. Native TLS or explicitly trusted exact proxy IPs are required
  by the framework's HTTPS detection API.
- Cookie configuration is host-only by default. Set `COOKIE_DOMAIN`, `COOKIE_PATH`, and
  `COOKIE_SAMESITE` when required. `COOKIE_SECURE` defaults to true in configuration;
  `.env.example` explicitly disables it for local HTTP development. Set it to true for HTTPS
  deployment. PHP session cookies use the same settings and no longer advertise public caching.
- Authentication lifetime is 3600 seconds, or 2592000 seconds when remembering a user.
  Both values must stay positive. Existing 3.x cookies are rejected and users must sign in again.
  Logout requires a CSRF-protected POST and deletes the browser cookie; GET renders a confirmation
  form without expiring authentication. Immediate replay revocation requires repository token
  invalidation. Password changes rotate the application's user token and redirect to login.
- Keep `APP_DEBUG=false` in production. HTTP exception handling is enabled by default.
  Debug-bar middleware is registered only when `APP_DEBUG=true`; request headers cannot enable
  it in production. Bootstrap failures propagate as exceptions rather than being returned as message strings.
- The application exception middleware accepts PDO SQLSTATE strings and renders a generic JSON 500
  without failing while converting the code. Account operations handle database failures with generic
  flash messages; duplicate registration does not leave an orphaned creation event.
- CORS runs before the HTTP exception handler, firewall, CSRF, and route authentication.
  A final OPTIONS route lets preflights reach CORS for endpoints declaring other methods;
  explicit application OPTIONS routes take precedence. Ordinary unmatched OPTIONS requests
  return 404. Wildcard origins permit no credentials. Configure explicit trusted origins before
  setting `access-control-allow-credentials` to true.
- The firewall has an application logger binding and a local blocked-response template, so a
  detected request returns 403 instead of falling into exception handling. Rule metadata is
  logged, while the matched request value is omitted unless `firewall.log_payload` is explicitly
  set to true.
- CSRF preparation precedes protection, and every supplied HTML form includes `csrf_field()`.
  The obsolete CSRF salt and configurable token-length settings were removed. A generated
  server token does not prove client submission.
- The configured application `WebsiteManager` renders the local templates, including CSRF-protected
  creation, editing, settings, and deletion forms. Page deletion rejects non-POST requests with 405.
  Page names are escaped in deletion dialogs. `PageEditor` sends the CSRF header for same-origin
  jQuery requests and GrapesJS uploads; page saves and asset mutations require POST.
- The firewall permits HTML in the editor's POST `data` field only, so embedded media can be saved.
  CSRF and `vihzhuo:manage` authorization remain required. Other fields, paths, methods, and firewall
  rules retain their checks. If changing the editor URL, update this exact-path exclusion too.
- Login and registration share the configured limit of five requests per ten minutes by
  connection address. Responses exceeding it use 429 and Retry-After. Clients behind NAT
  or a proxy share buckets unless an application supplies a trusted identity resolver.
  The PSR-6 limiter is best effort; strict concurrent quotas require an atomic backend.
- The SSRF firewall rule checks submitted query/body destinations rather than the application's
  own URL, allowing local development hosts. Payload logging remains disabled. If other request
  fields drive outbound requests, configure their sources explicitly and validate destinations.

`StoreUserValidator` now validates username and password before creating its DTO. Usernames
follow the existing value-object format (lowercase letters, underscores, and hyphens, up to
20 characters), with the configured minimum length. Passwords use the configured minimum.

Public registration uses `RegisterUserValidator`, which assigns `user` on the server regardless
of submitted roles. Regular users can access the dashboard and their own profile; listing and
managing users requires administrative permissions. Profile edits preserve the stored role
and derive the user ID from the authenticated user. Password changes do the same and require
confirmation. Custom forms must not depend on client-selected identity or profile role changes.
The catch-all page route is registered after the account and administration routes, and an
unresolved public page returns an actual 404 status.

Successful password authentication calls `Password::needsRehash()` and updates an outdated
hash in the authentication table. The update also matches the old hash to avoid overwriting a
concurrent password change. This upgrades the authentication projection; replaying old creation
or password events can restore their historical hashes, which are upgraded again on login.
The configured token column is honored for authentication and lookup.

## Queues, scheduling, transactions, and storage

`config/queue.php` ships an empty `jobs` allowlist. Add only persistent jobs implementing
`SerializableJob`, validate their payloads, and use one class per queue name. No application jobs
ship with this pagebuilder. Before upgrading existing installations, stop workers, back up node files,
and re-enqueue legacy jobs from trusted data into a new node. See [queues](queues.md).

The application provider binds `Locker` to `FileLocker` under `storage/scheduler-locks`.
`onlyOneInstance()` holds the lock through foreground completion. Scheduler command failures
produce nonzero exits; supervision should inspect those exits. Do not delete queue or scheduler
lock files while workers are running. File locks require a shared local filesystem; multi-host
execution requires a distributed lock backend. See [scheduling](scheduler.md).

The framework PDO provider shares the same connection used by application database services.
Transactional pipelines must use that injected connection and cannot nest inside an existing
transaction. Do not wrap the event store's own transactions in another transactional pipeline.
See [validation and pipelines](validation-and-pipelines.md).

Event batches now commit atomically. The user repository wraps event storage and its synchronous
projection in one database transaction, using the connection's nested savepoints. A failed projection
rolls both back and preserves pending aggregate events. Event history is explicitly ordered by
playhead; `loadFromPlayhead()` includes all events at or above the requested playhead. Invalid JSON
payloads fail instead of being persisted as empty data. Custom repository construction must supply
the same `Database` connection used by the event store and projection.

Local storage defaults to private visibility. Private file and directory modes are corrected to
0600 and 0700. The explicit public disk remains public. Existing filesystem permissions are not
changed automatically. Queue JSON and lock files and scheduler locks are ignored by Git; custom
node locations should also be excluded. Store runtime files outside `public/`.

Mailer configuration now uses Symfony Mailer DSNs (`MAILER_DSN` and the named transport DSNs).
Optional logger email addresses are commented out in `.env.example`; leaving present-but-empty
addresses can make an error reporter fail while constructing an invalid email address.

## Upgrade order and verification

1. Back up data, existing `.enc.key`, and queue files; stop workers and schedulers.
2. Review custom validation fields, middleware aliases, RBAC graphs, cookies, and environment
   settings. Repair cyclic persisted RBAC data; do not delete it without preserving custom roles.
3. Resolve dependencies and retain `composer.lock` in the application repository. The pagebuilder
   no longer ignores its lock. Guzzle is supplied transitively by the framework; do not add a direct
   application requirement. Vulnerable PHP_CodeSniffer versions are excluded. Deploy all nodes with
   the same lock and authentication-cookie format.
4. Migrate jobs and review application tests. Run `composer test`, `composer cs-check`,
   `composer codestan`, `composer validate --no-check-publish`, and `composer audit --locked`.
5. Restart workers with the new code/configuration and have users sign in again.

Tests use temporary configuration and session storage and an isolated SQLite database. The
suite covers validated DTO fields, role and identity boundaries, password rehashing, guest
request context, CSRF, auth-cookie rejection, CORS, firewall inputs, PDO transactions, and locks.
Static analysis scans `src` at level 8. Coding standards cover bootstrap, config, public, routes,
and all source files, including simplified redundant paginator property hooks.

No known vulnerability advisories were returned during this update; this does
not guarantee the absence of vulnerabilities. See the framework [release review](release-review.md)
for its additional deployment and concurrency limitations.

## Verification of this pagebuilder update

- PHP 8.4 and PHP 8.5: 25 tests passed, 88 assertions each.
- Coding standards: no errors or warnings.
- PHPStan level 8: no errors. PHP 8.4 source/template syntax checks passed.
- Fresh SQLite migrations, queue listing, and the empty scheduler completed successfully.
- Composer manifest valid.
- Composer audit: empty advisories list.
- Full HTTP regressions cover manager forms, editor CSRF integration, HTML saves, POST-only
  deletion/logout, and rejected mutations without CSRF. Database regressions cover atomic batches,
  projection rollback, invalid JSON, and ordered event replay.
- Documentation links and diff whitespace passed.

SMTP, multi-host storage, and live Swoole were not tested.
