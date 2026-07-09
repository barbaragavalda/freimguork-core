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

The framework was written ~10 years ago; it is currently being modernized in stages. Routing was
rewritten from scratch as a clean-break v2 (see "Routing" below). Dependency Injection followed as
its own phase (see "Dependency Injection" below) — a `Core\Container\Container` with `Bootstrap` as
the composition root. A broader test suite is the next planned phase. Expect a mix of very old,
singleton/superglobal-heavy code (`Config`, `Session`) — which the DI phase deliberately wraps
rather than replaces — alongside the newer, PSR-7/attribute-based routing and the container.

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
   (`Core\Utils\Config::loadConfigs()`), resolves the language, strips that sub-project's resolved
   domain prefix (e.g. the language segment) from the request path (see "Domain prefix stripping"
   under Routing below), then loads/matches routes for that sub-project (see "Routing" below) into
   a `Core\Routing\RouteMatch`.
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
object. This class pair is considered stable/foundational — routing and DI build on top of it
rather than replacing it (`Bootstrap` registers the resolved `Projects`/`Project` as container
instances — see "Dependency Injection" below).

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
  derives whatever URL prefix is needed (empty if the entry script already sits at the web root,
  `<actual-folder-name>/` if it's one level down) from `dirname($_SERVER['SCRIPT_NAME'])` — a
  URL-space path Apache itself resolves from the request/rewrite (e.g. `/index.php` or
  `/public/index.php`), not a filesystem path. **Deliberately not** `$_SERVER['DOCUMENT_ROOT']` vs
  `dirname($_SERVER['SCRIPT_FILENAME'])`, which was the first implementation and is broken under a
  reverse proxy where Apache and PHP-FPM run in separate containers (this project's own local dev
  VM does exactly this): those are filesystem paths, and they live in different filesystem
  namespaces per-container (`/usr/local/apache2/htdocs/...` for Apache, `/var/www/html/...` for
  PHP-FPM here) even when they point at "the same" directory, so the comparison silently always
  concluded "prefix needed" — this exact bug shipped, broke `cuina-de-profit-local`'s asset URLs
  right after its `public/`→`web/` migration, and needed a follow-up fix; don't reintroduce a
  filesystem-path comparison here if this is ever touched again. `Controller::__construct()`
  (static asset URLs) and `Model\File::__construct()` (upload URLs) both use it; before either
  implementation existed, both hardcoded the literal string `'public'`, which would have silently
  produced wrong URLs the moment any app renamed that folder. With the `SCRIPT_NAME`-based
  implementation, migrating an app's `public/` to `web/` only needs the folder itself and the
  app's own `.htaccess`/`robots.txt`/vhost updating — no further framework code changes.
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
- `RouteCompiler.php` — pure functions: `compile()` turns a path pattern into a regex (`{name}`
  required, `{name?}` optional-trailing, per-param `requirements` override the default `[^/]+`);
  `normalizePath()`/`splitPath()`/`stripPrefix()` are path-string helpers reused by `Bootstrap`
  (domain-prefix stripping, `$parts` — see below) precisely so that logic is unit-testable without
  needing a full `Bootstrap` (which pulls in `Config`/DB dependencies)
- `Route.php` / `RouteCollection.php` / `RouteMatch.php` — value objects; `RouteCollection`
  round-trips through `toArray()`/`fromArray()` for caching
- `Loader/AttributeRouteLoader.php` — scans a given directory + namespace via `ReflectionClass`
  for controllers (subclasses of `Core\Controller\Controller`, non-abstract) and their `#[Route]`
  attributes. **Takes no globals/constants** — directory, namespace, and optional cache file path
  are all explicit parameters, which is what makes it unit-testable without a web server.
  Two ways to attach routes to a controller: (1) method-level `#[Route]` for multi-action
  controllers (class-level `#[Route]`, if present, acts as a path prefix shared by every attributed
  method) — see `BlogController` above; (2) **class-level-only** `#[Route]` (no `#[Route]` on any
  method of the class itself) for the single-action controllers this framework family
  predominantly uses, where a shared abstract base class defines `build()` once and every concrete
  page just overrides a hook method (conventionally `run()`) — since a `#[Route]` can't be
  discovered on an inherited method (the loader only reads attributes declared directly on the
  concrete class), a class-level-only `#[Route]` is instead treated as "this whole class is one
  route, dispatching to `build()`". Multiple class-level `#[Route]` attributes on the same
  single-action class each register as independent full routes (order matters when they overlap —
  see `Potato\Map` in `cuina-de-profit-local` for a real example: the more specific `/braves/pro/...`
  attribute must come before the more general `/braves/...` one, since attributes are read in
  declaration order and the router returns the first match).
- `Router.php` — `match(ServerRequestInterface): RouteMatch` (throws `RouteNotFoundException` /
  `MethodNotAllowedException`) and `generate(name, params): string` for reverse routing. Uses
  `Psr\Http\Message\ServerRequestInterface` as the match input (built via Guzzle's `ServerRequest`,
  already a dependency) specifically so routing logic never touches superglobals directly.
  Also exposes a `Router::getCurrent()`/`setCurrent()` static holder so `path()`/`url()` Twig
  functions can reach the current request's router. It's now backed by `Core\Container\Container`
  (see "Dependency Injection" below) rather than its own private static — still a static facade,
  since `View\Extension\Twig` isn't itself part of the container's object graph, but no longer the
  ad hoc stopgap it used to be.

Per-project route caching follows the same `IS_DEV` convention as the existing disk cache
(`Core\Controller\Cache\Disk`): in dev, routes are rescanned from disk on every request; in prod,
they're compiled once to `src/cache/prod/freimguork/routes-<app>-<language>.php` and reused (keyed
per language too — see below). This is wired up in `Bootstrap::loadRoutes()` — the loader itself is
cache-convention-agnostic.

**Translated URL slugs.** The old `routing.php` configs built SEO-friendly, per-language paths by
wrapping literal segments in gettext, e.g. `_('recepta') . '/{uri}'`. PHP attribute arguments must
be compile-time constants, so that can't be written directly inside `#[Route(...)]` — instead,
write the canonical/source-language literal straight into the attribute
(`#[Route('/recepta/{uri}')]`) and `AttributeRouteLoader::translatePath()` runs every *literal*
(non-`{param}`) path segment through gettext's `_()` unconditionally while building each `Route`.
Segments with no matching msgid are returned unchanged by gettext itself, so this is safe even for
routes that were never meant to be translated — no per-route opt-in/out needed. This only works
because `Bootstrap::router()` already resolves the language and initializes gettext
(`Language::initGettext()`, via `setlocale`/`bindtextdomain`/`textdomain`) *before* `loadRoutes()`
runs, exactly mirroring how the old `routing.php` `include` timing worked. It's also why the prod
route cache is keyed per language, not just per app — two languages of the same app compile to
different paths/regexes for the same logical route (the route *name* stays language-invariant, so
`path('recipe.detail')` in Twig keeps working regardless of the active language).

**Domain prefix stripping.** Routes are defined relative to a sub-project, without whatever prefix
that sub-project's resolved domain adds — most commonly the language segment for a `{lang}`-keyed
project (e.g. `#[Route('/receptes')]`, not `#[Route('/{lang}/receptes')]`). A real request's raw
path includes that prefix (`/ca/receptes`), so `Bootstrap::router()` strips it — via
`RouteCompiler::stripPrefix($path, $prefix)`, where `$prefix` is
`parse_url($config->getDomain(), PHP_URL_PATH)` — before ever calling `Router::match()`, and stores
the stripped path as `$petitionPath` for `Controller::$parts` to use too (see below). **This is not
optional or Web-project-specific** — it's the same for every sub-project, including ones with a
literal (non-`{lang}`) prefix like `cronjob`/`wallaby`/`import`, since `Projects::getDomains()`
resolves a domain path for all of them. Forgetting this step (which the original routing rewrite
did) means every route silently fails to match and every request falls through to the 404
fallback — a real bug that shipped and needed a follow-up fix, so don't reintroduce it if this
logic is ever touched again. The old `URL::loadParams()` did the equivalent stripping against
`$config->getDomain()` before matching against `routing.php`.

Two framework-invoked "special" controllers bypass normal attribute routing and are dispatched
directly by `Bootstrap::router()`, by calling `build()` — the same conventional entrypoint every
other controller in this framework family already uses (see the class-level-only routing
convention above), so neither needed a special method name of its own:
- `<App>\Controller\DefaultController` — fallback when no route matches (404/405)
- `Core\Controller\RedirectLang` — redirects to the language-prefixed URL when a project requires
  a language in the URL but the request didn't include one

**Known migration debt**: most consuming apps (`freimguork-appacman`, `freimguork-webservice`,
`freimguork-jwt`, most `*-local` sites) still have `routing.php` config files — those need
migrating to `#[Route]` attributes (method-level for multi-action controllers, class-level-only
for the far more common shared-`build()`-plus-`run()`-hook single-action pattern — no method
renames needed either way, since `build()` is already the universal convention) before those apps
will work against this version of core. **`cuina-de-profit-local`'s `Web`/`Cronjob`/`Import`
sub-projects are already migrated** (its `config/{web,cronjob,import}/routing.php` are gone,
replaced by attributes directly on those controllers) — treat that migration as the reference
example for doing the rest, including how it handles two real wrinkles: `Potato\Map` needs two
ordered class-level `#[Route]` attributes (see the ordering note above) since collapsing
`/braves/pro/...` and `/braves/...` into one pattern would let a bare `{param1}` swallow the
literal `pro` segment, and `Recipe\Detail` drops a second routing.php entry that mapped to the
exact same controller action as the general one and was already unreachable under the old router
too. The rest of that migration is intentionally out of scope for core itself, with one exception
already found and fixed: `Controller::$parts`/`setParts()` (the raw literal URL path segments, e.g.
for `in_array('pro', $this->parts)`-style checks) was mistakenly deleted as apparently-dead code
during the rewrite — it's actively used by `freimguork-appacman`, `freimguork-webservice`, and
several `*-local` apps. It's restored, now populated by `Bootstrap` from the same
domain-prefix-stripped path used for route matching (see "Domain prefix stripping" above), not
from route matching itself.

`freimguork-appacman` specifically has one more gap beyond routing.php migration, found but not
yet fixed (out of scope until that package is tackled): `Bootstrap::loadRoutes()` assumes a
sub-project's controllers live under `<app-root>/src/<App>/Controller/`, which is wrong for
Appacman — its controllers live in the separate `freimguork-appacman` package
(`vendor/optisistem/freimguork-appacman/src/Controller/`, namespace `Appacman\`); `View.php` and
`Language.php` already special-case this, `Bootstrap` doesn't yet. (Its shared-`build()` dispatch
pattern is otherwise identical to `cuina-de-profit-local`'s Web controllers and is already handled
by the class-level-only routing convention above — that part of the original concern is resolved.)

### Dependency Injection

`Core\Container\Container` (`src/Container/`) is a minimal PSR-11 autowiring container. The rule
that makes it correct: **only ids explicitly registered via `instance()`/`singleton()` are shared —
everything else it builds via constructor reflection is a fresh instance every call.** This matters
concretely for `Core\Controller\CacheManager`: `Bootstrap` and `Controller` each need their *own*
instance (they cache different things, and the object is stateful across a `getCache()`/
`saveCache()` call pair), so it must never be silently promoted to a shared singleton just because
it was autowired.

`Core\Bootstrap` is the composition root. Its constructor creates the container, publishes it via
`Container::setCurrent()`, and registers `Config`/`Session`/the default DB connection
(`Core\Model\MySQL\PDO`) as container-backed singletons that just wrap the existing
`::getInstance()` calls — those classes themselves are untouched, so every other direct
`::getInstance()` call site (including in every consuming app) still returns the exact same object.
`Bootstrap::execute()` builds the matched controller via `$container->make($controllerClass)`
instead of a bare `new`, which is what makes controller-level constructor injection actually happen.

- `Core\Controller\Controller` takes `Config $config, CacheManager $modelCache` as **required**
  constructor parameters (no defaults) — safe because every controller in every consuming app is
  built by the container, as long as any app-level intermediate base class forwards them (see
  "Known migration debt" below).
- `Core\Controller\CacheManager` takes `Disk $cache` as a required parameter too.
- `Core\Model\Model` is the deliberate exception: its constructor takes **optional**
  `?PDO $mysql = null, ?Session $session = null`, defaulting to `Manager::getInstance()`/
  `Session::getInstance()`. This is not a consuming-app back-compat shim — `Model` is subclassed by
  `File`/`Language`/`Form`/`Push`/`Paginated`, and `Model.php` itself does `new File($fileID)` deep
  inside its own methods, none of which goes through the container; making those dependencies
  required would mean rewriting every subclass's constructor just to keep *core itself* working,
  without reaching any consuming app anyway. Keeping them optional is what makes
  `new Model(new PDO([], false), new Session())` work in a test without a real database connection
  (see "Testing conventions" below).
- `Core\Routing\Router::getCurrent()`/`setCurrent()` (see "Routing" above) is a pure proxy to the
  container instead of its own private static.

**Known migration debt**: a consuming app whose own Controller (or Model) base class declares its
own `__construct()` calling `parent::__construct()` with no arguments will break against this
version of core, since `Controller`'s new dependencies are required — that base class needs to
accept and forward `Config`/`CacheManager` itself. `cuina-de-profit-local`'s
`Web\Controller\Controller` (the shared single-action-controller base every `Web` controller
extends) is already migrated — treat it as the reference example. `freimguork-appacman`,
`freimguork-webservice`, `freimguork-jwt`, and the rest of the `*-local` sites have not been
checked/migrated yet.

### Controllers and Models
- `Core\Controller\Controller` (abstract base) — every request-handling controller extends this.
  Constructor wires up domain/language/static-path template variables via `assign()` (see
  "Dependency Injection" above for how `Config`/`CacheManager` reach it). Actions are arbitrary
  public methods (see Routing above) invoked through `dispatch()`, not a single fixed entrypoint.
  `getCacheDef()` (override to opt into response caching), `loadCache()` (model-level caching),
  `getHTML()` (render a Twig fragment to a string, e.g. for emails/PDFs).
- `Core\Model\Model` (base for all models) — wraps a `Core\Model\MySQL\PDO` connection, either
  injected (see "Dependency Injection" above) or defaulted to
  `Core\Model\MySQL\Manager::getInstance()` (keyed by connection name, config comes from the `db`
  config key). Uses `__call()` to proxy unknown method calls straight to the PDO wrapper.
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
against real files/reflection rather than mocks), `Core\Utils\Config::getWebFolderPrefix()`
(`tests/Utils/`), `Core\Container\Container` (`tests/Container/`, plus fixture classes under
`tests/Fixtures/Container/` covering no-constructor/typed-dependency/scalar-default/nullable-typed/
unresolvable-parameter cases), and a first slice of `Core\Model\Model` (`tests/Model/ModelTest.php`,
using an injected disconnected `Core\Model\MySQL\PDO` — `new PDO([], false)` leaves the internal
`\PDO` null, so `query()` just returns `[]` — instead of a real database connection, now that
`Model`'s constructor accepts one). Follow the same pattern for new tests — real PSR-7 requests via
`GuzzleHttp\Psr7\ServerRequest`, real fixture classes/superglobal values, no mocking framework. Most
of `Core\Model\*`/`Core\Utils\Language` still isn't unit-tested — the DI phase made the dependencies
swappable, but writing tests for each class is still open work.
