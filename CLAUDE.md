# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

`freimguork-core` is the shared core library of "Freimguork," a small, lightweight, in-house
PHP framework (own routing, DB layer, Twig-based views — no Symfony/Laravel dependency). It is
never run standalone: it's installed as a Composer dependency (`optisistem/freimguork-core`) by
consuming applications (each app's own repo has a `src/<App>/...` tree with its own controllers,
plus a `config/` directory), and there is no way to `run` or exercise it outside of a real host
app. Sibling packages in this family include `freimguork-appacman` (admin panel), `freimguork-webservice`,
and `freimguork-jwt`.

The framework was written ~10 years ago; it is currently being modernized in stages (Routing was
just rewritten from scratch as a clean-break v2 — see "Routing" below — DI and a broader test
suite are the next planned phases). Expect a mix of very old, singleton/superglobal-heavy code
(`Config`, `Session`, `Model`) alongside the newer, PSR-7/attribute-based routing.

## Commands

This repo has no local PHP/Composer toolchain on the host — PHP only exists inside the project's
Docker container (see the top-level `VM/docker-compose.yml`, service `php`, which mounts
`storage/src/` to `/var/www/html`). Run all PHP/Composer commands through it:

```bash
docker exec php sh -c "cd /var/www/html/freimguork-core && composer install"

# run the full test suite
docker exec php sh -c "cd /var/www/html/freimguork-core && vendor/bin/phpunit"
# equivalent: composer test

# run a single test file / method
docker exec php sh -c "cd /var/www/html/freimguork-core && vendor/bin/phpunit tests/Routing/RouterTest.php"
docker exec php sh -c "cd /var/www/html/freimguork-core && vendor/bin/phpunit --filter testMatchesDynamicPathAndExtractsParams"

# lint a file
docker exec php sh -c "cd /var/www/html/freimguork-core && php -l src/Routing/Router.php"
```

If a package requirement changes, use `composer update <package>` (not a bare `composer install`)
so the lock file only re-resolves what actually changed — this repo has a lot of vendored
dependencies (Google API client, PhpSpreadsheet, Twig, etc.) and a full re-lock is slow and noisy.

There is no separate lint/static-analysis tool configured (no phpcs/phpstan config in this repo).

## Architecture

### Request lifecycle
Every request through a consuming app funnels through `Core\Bootstrap`:
1. `Bootstrap::__construct(bool $isDev)` defines the `IS_DEV` global constant and builds a PSR-7
   request via `GuzzleHttp\Psr7\ServerRequest::fromGlobals()`.
2. `Bootstrap::router()` resolves which **sub-project** the request belongs to (see "Multi-project
   structure" below) via `Core\Routing\Projects`/`Project`, loads that sub-project's config
   (`Core\Utils\Config::loadConfigs()`), resolves the language, then loads/matches routes for that
   sub-project (see "Routing" below) into a `Core\Routing\RouteMatch`.
3. `Bootstrap::execute()` instantiates the matched controller class, applies controller-level
   response caching (`Core\Controller\CacheManager` + `Core\Controller\Cache\Disk`), and calls
   `Controller::dispatch($action)` to invoke the matched action method.
4. The controller assigns view data (`Controller::assign()`) and finishes by calling one of
   `template()` / `json()` / `xml()` / `redirect()` / `export()`, which hands off to `Core\View\View`
   and a `Core\View\Response\*` class (`HTML` = Twig, `Json`, `XML`, `CSV`, `Redirect`, `Mail`).

### Multi-project structure
A single consuming app repo is not necessarily one application — `config/projects.dev.php` and
`config/projects.prod.php` each define a map of **sub-projects** (e.g. the public `Web` site,
an `Appacman` admin panel, an `Import` tool, a `Cronjob` runner), keyed by a domain/path pattern
(`{lang}` for the language-prefixed public site, or a literal prefix like `wallaby`/`cronjob`).
Each sub-project entry has:
- `app` — the PSR-4 root namespace and `src/<App>/` directory holding that sub-project's own
  `Controller/` (and `Model/`, `View/`) tree
- `folders` — the `config/<folder>/` directory to load additional config from
- `languages` — languages that sub-project supports

**The set of sub-projects can differ between dev and prod** (e.g. an `import` tool only defined
in `projects.dev.php`) — this is intentional, not a bug to "fix". `Core\Routing\Projects` resolves
which sub-project the current request belongs to; `Core\Routing\Project` is the resolved value
object. This class pair is considered stable/foundational — routing and future DI work builds on
top of it rather than replacing it.

### Config layering
`Core\Utils\Config` (singleton via `getInstance()`) layers config files from, in order: `config/`,
then `config/dev/` or `config/prod/` (chosen by the `IS_DEV` constant), then per-sub-project
folders (`config/<folder>/`) and per-sub-project-per-env folders (`config/<folder>/dev|prod/`),
plus any `vendor/optisistem/*/src/config/` package configs. All matching `.php` files in each
directory are `include`d and their `$config` arrays merged (`array_merge_recursive`). Anything
env-specific (DB credentials, mail settings, etc.) belongs in the `dev|prod` folders; anything
sub-project-specific but env-agnostic (like routing used to be) belongs directly under
`config/<folder>/`. This dev/prod split is load-bearing for the whole framework, including the
routing route-cache convention below — don't bypass it.

### Consuming app structure & deployment security
A standard app built on this framework has: `config/`, `locale/`, `public/`, `src/`, `vendor/`,
plus `.gitignore`, `.htaccess`, `composer.json`, `robots.txt` at its root. **Only the web-exposed
folder should ever be reachable over HTTP** — `config/` (holds DB/mail/encryption credentials),
`src/`, `vendor/`, and `locale/` must never sit inside the web-served tree. How that isolation is
actually achieved depends on the deployment target, and it's currently inconsistent:

- **Production (this team's actual host, Cdmon, via FTP only — no Apache/PHP config access)**:
  `DocumentRoot` can't be changed at all here, but it doesn't need to be — a Cdmon hosting account's
  FTP root has a fixed, non-negotiable layout: `backup_db/`, `cert/`, `errors/`, `logs/`, `tmp/`, and
  `web/` as siblings, where `web/` is the one folder that's actually served. The target structure is
  to make the app repo mirror that exactly: rename the app's `public/` to `web/`, and deploy so that
  `web/`'s *contents* (`index.php`, `.htaccess`, `static/`, `upload/`, `robots.txt`, etc.) land inside
  Cdmon's `web/`, while `config/`, `locale/`, `src/`, `vendor/`, `composer.json` land as FTP-root
  siblings next to `backup_db/cert/errors/logs/tmp` — never inside `web/`. Since each app's entry
  script already defines `DIR_ROOT` as a relative `'../'` (see `public/index.php`), this requires no
  path changes: `DIR_ROOT` resolves correctly either way, since it's always "one directory up from
  wherever the entry script sits."
- **Local dev VM** (this monorepo's `docker-compose.yml` + Apache — see
  `templates/apache/new-vhost.conf`, which every site's vhost under `storage/config/apache/*.conf`
  is stamped from via `system/scripts/host.sh create`): `templates/apache/new-vhost.conf` now points
  `DocumentRoot`/`<Directory>`/the `ProxyPassMatch` target at `{HOST_NAME_ALIAS}/web` instead of the
  app's repo root, and `system/scripts/host.sh`'s `create` command scaffolds its sanity-check
  `index.html` into a `web/` subfolder to match. This fixes host creation **going forward only** —
  existing sites' already-materialized `storage/config/apache/*.conf` files are untouched (they're
  copies made at creation time, not regenerated from the template), and none of them will actually
  work with the new template until that app's own `public/` is renamed to `web/` (see above) and its
  `.conf` is regenerated/edited to match. Until an app makes that move, it keeps running exactly as
  before (`DocumentRoot` = repo root, isolation via the extension-whitelist `.htaccess`, with its
  known gaps: `.json` is on the whitelist so `composer.json` at the repo root is directly
  web-servable; `Options Indexes` is also enabled, so any directory without an index file gets
  listed).
- **The web-folder name is no longer hardcoded in this framework.** `Core\Utils\Config::getWebFolderPrefix()`
  derives whatever URL prefix is needed (empty if `DocumentRoot` already points straight at the web
  folder, `<actual-folder-name>/` if `DocumentRoot` is an ancestor of it) by comparing
  `$_SERVER['DOCUMENT_ROOT']` against the entry script's own directory — it does not care whether
  that folder is named `public`, `web`, or anything else. `Controller::__construct()` (static asset
  URLs) and `Model\File::__construct()` (upload URLs) both use it; previously they hardcoded the
  literal string `'public'`, which would have silently produced wrong URLs the moment any app
  renamed that folder. When migrating an app's `public/` to `web/`, only the folder itself and the
  app's own `.htaccess`/`robots.txt` need updating — no framework code changes required.
- **Known issue, found in at least one live app (`cuina-de-profit-local`)**: real credentials
  (`config/dev/db.php`, `config/prod/db.php`, `config/keys.php`, `config/mail.php`) and a full `.sql`
  database dump are committed to git, with the dump recurring across multiple "database update"
  commits. Never commit real credentials or DB dumps — gitignore them and keep per-environment
  config untracked. Because these are already in git history, treat every committed credential as
  compromised: rotating the DB passwords/encryption keys/mail credentials and purging the blobs
  from history (e.g. `git filter-repo`) is a deliberate remediation task, not something to do as a
  side effect of an unrelated change.
- `robots.txt` `Disallow` rules on `config/`/`src/`/`vendor/` (already used in practice) are a
  courtesy to well-behaved crawlers only, not a security control — they don't stop anything from
  actually being requested.

### Routing (rewritten — attribute-based, HTTP-method-aware)
Controllers declare routes with PHP attributes instead of the old `routing.php` array files:

```php
#[Route('/blog')]                                        // class-level = path prefix
class BlogController extends Controller
{
    #[Route('/', name: 'blog.index')]
    public function index(): void { ... }

    #[Route('/{slug}', name: 'blog.show', requirements: ['slug' => '[a-z0-9-]+'])]
    public function show(): void { ... }

    #[Route('/{id}', name: 'blog.update', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function update(): void { ... }
}
```

Key pieces (`src/Routing/`):
- `Attribute/Route.php` — the attribute itself (`path`, `methods`, `name`, `requirements`)
- `RouteCompiler.php` — pure function turning a path pattern into a regex (`{name}` required,
  `{name?}` optional-trailing, per-param `requirements` override the default `[^/]+`)
- `Route.php` / `RouteCollection.php` / `RouteMatch.php` — value objects; `RouteCollection`
  round-trips through `toArray()`/`fromArray()` for caching
- `Loader/AttributeRouteLoader.php` — scans a given directory + namespace via `ReflectionClass`
  for controllers (subclasses of `Core\Controller\Controller`, non-abstract) and their `#[Route]`
  attributes. **Takes no globals/constants** — directory, namespace, and optional cache file path
  are all explicit parameters, which is what makes it unit-testable without a web server.
- `Router.php` — `match(ServerRequestInterface): RouteMatch` (throws `RouteNotFoundException` /
  `MethodNotAllowedException`) and `generate(name, params): string` for reverse routing. Uses
  `Psr\Http\Message\ServerRequestInterface` as the match input (built via Guzzle's `ServerRequest`,
  already a dependency) specifically so routing logic never touches superglobals directly.
  Also exposes a `Router::getCurrent()`/`setCurrent()` static holder — a deliberate stopgap (not a
  general pattern to imitate) so `path()`/`url()` Twig functions can reach the current request's
  router before a DI container exists.

Per-project route caching follows the same `IS_DEV` convention as the existing disk cache
(`Core\Controller\Cache\Disk`): in dev, routes are rescanned from disk on every request; in prod,
they're compiled once to `src/cache/prod/freimguork/routes-<app>.php` and reused. This is wired up
in `Bootstrap::loadRoutes()` — the loader itself is cache-convention-agnostic.

Two framework-invoked "special" controllers bypass normal attribute routing and are dispatched
by a fixed method name, `handle()`, set directly in `Bootstrap::router()`:
- `<App>\Controller\DefaultController` — fallback when no route matches (404/405)
- `Core\Controller\RedirectLang` — redirects to the language-prefixed URL when a project requires
  a language in the URL but the request didn't include one

**Known migration debt**: consuming apps (`freimguork-appacman`, `freimguork-webservice`,
`freimguork-jwt`, the `*-local` sites) still have `routing.php` config files and
`DefaultController::build()`/`run()` methods from the old router — those need migrating to
`#[Route]` attributes and a `handle()` method respectively before those apps will work against
this version of core. That migration is intentionally out of scope for core itself.

### Controllers and Models
- `Core\Controller\Controller` (abstract base) — every request-handling controller extends this.
  Constructor wires up domain/language/static-path template variables via `assign()`. Actions are
  arbitrary public methods (see Routing above) invoked through `dispatch()`, not a single fixed
  entrypoint. `getCacheDef()` (override to opt into response caching), `loadCache()` (model-level
  caching), `getHTML()` (render a Twig fragment to a string, e.g. for emails/PDFs).
- `Core\Model\Model` (base for all models) — wraps a `Core\Model\MySQL\PDO` connection
  (`Core\Model\MySQL\Manager::getInstance()`, keyed by connection name, config comes from the
  `db` config key). Uses `__call()` to proxy unknown method calls straight to the PDO wrapper.
  `Core\Utils\Language` also extends `Model` (it needs DB access to resolve language IDs).
- Encryption helpers live in `Core\Model\Encryptor\{OneWay,TwoWay}`; file/image handling in
  `Core\Model\File`; spreadsheet export in `Core\Model\Utils\Excel`; push notifications in
  `Core\Model\Push\*`.

### Views
`Core\View\View` picks a `Core\View\Response\*` renderer based on which method the controller
called (`template()` → `HTML`, `json()` → `Json`, `xml()` → `XML`, `redirect()` → `Redirect`,
`export()` → `CSV`). `HTML` sets up a Twig `Environment` scoped to `src/<App>/View/` (plus
`.../freimguork-appacman/View/` when the app is `Appacman`), with gettext-based translation
(`jblond/twig-trans`) and the custom `Core\View\Extension\Twig` extension (custom filters like
`formatPrice`/`customTrans`, and the `path()`/`url()` reverse-routing functions).

## Testing conventions

Tests live in `tests/`, PSR-4 autoloaded as `Core\Tests\` (see `composer.json` `autoload-dev` and
`phpunit.xml`, bootstrap `tests/bootstrap.php` — which also defines a placeholder `DIR_ROOT`, since
that constant is normally defined by a consuming app's `public/index.php` before anything else
runs, and tests bypass that entry point). Coverage so far is `Core\Routing` (`tests/Routing/`, plus
fixture controllers under `tests/Fixtures/Controller/` used to exercise `AttributeRouteLoader`
against real files/reflection rather than mocks) and `Core\Utils\Config::getWebFolderPrefix()`
(`tests/Utils/`). Follow the same pattern for new tests — real PSR-7 requests via
`GuzzleHttp\Psr7\ServerRequest`, real fixture classes/superglobal values, no mocking framework.
Anything that needs a real MySQL connection (most of `Core\Model\*`, `Core\Utils\Language`) isn't
unit-tested yet — that's expected until the DI phase makes those dependencies swappable.
