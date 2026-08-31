# freimguork-core

Core module for Freimguork — a small, lightweight, in-house PHP framework (own routing, DB layer,
Twig-based views) with no Symfony/Laravel dependency. This package is never run standalone: it's
installed as a Composer dependency by consuming applications, which provide their own `src/<App>/`
controller trees, `config/`, `locale/`, and web-exposed folder.

Sibling packages in this family: `freimguork-appacman` (admin panel), `freimguork-webservice`,
`freimguork-jwt`.

## Requirements

- PHP ^8.5, with the `curl`, `exif`, `gettext`, `gd`, `intl`, `openssl`, `pdo` extensions
- A consuming application providing the expected folder layout (`config/`, `locale/`, `src/`, a
  web-exposed folder, `composer.json`) — see `CLAUDE.md` for the full structure and its deployment
  security model

## Installation

```bash
composer require optisistem/freimguork-core
```

This is a private Bitbucket package. Composer needs to authenticate to `bitbucket.org` to fetch
it — Bitbucket app passwords are deprecated, so use an Atlassian API token with the
`read:repository:bitbucket` scope instead:

```bash
composer config --global http-basic.bitbucket.org "your-atlassian-account-email@example.com" "your-api-token"
```

## Versioning

`v1.0` tags the last pre-modernization snapshot (old `routing.php` config-based routing, no DI
container, echo/`header()`-based responses). Consuming apps that haven't migrated their controller
constructors to the new required-dependency shape yet pin to it explicitly
(`"optisistem/freimguork-core": "v1.0"`). `dev-master` is the actively modernized line — apps that
have already migrated (`freimguork-appacman`, `freimguork-webservice`) track it directly. See
`CLAUDE.md`'s "Known migration debt" notes for what still needs migrating before an app can safely
move off `v1.0`.

## Secrets management

Per-environment credentials load from `config/dev/`/`config/prod/` and are never committed with
real values: commit a `<file>.php.dist` with placeholders, gitignore the real `<file>.php`, each
environment fills in its own copy locally. See `CLAUDE.md`'s "Config layering" and "Known issue"
sections for the full convention and its current gaps.

## Routing

Controllers declare routes with PHP attributes:

```php
#[Route('/blog')]
class BlogController extends Controller
{
    #[Route('/', name: 'blog.index')]
    public function index(): void { /* ... */ }

    #[Route('/{slug}', name: 'blog.show', requirements: ['slug' => '[a-z0-9-]+'])]
    public function show(): void { /* ... */ }
}
```

Controllers that share one inherited `build()` entrypoint (a common pattern in this framework
family, where per-page behavior lives in an overridden hook method instead) use a class-level-only
`#[Route]` instead of a method-level one — see `CLAUDE.md` for the full routing model, including
translated URL slugs and the domain-prefix handling multi-language sites need.

## Testing

PHP and Composer only exist inside this project's Docker container (see the top-level
`VM/docker-compose.yml`, service `php`). Run all commands through it:

```bash
docker exec php sh -c "cd /var/www/html/freimguork-core && composer install"
docker exec php sh -c "cd /var/www/html/freimguork-core && vendor/bin/phpunit"
docker exec php sh -c "cd /var/www/html/freimguork-core && composer phpstan"
```

## Status

~10 years old, being modernized in stages: routing (attribute-based v2, done), dependency
injection (PSR-11 container, done), response layer (PSR-7, done), static analysis (PHPStan level
5, done with a baseline for pre-existing issues). Broader unit test coverage and migrating every
consuming app to the new routing/constructors are still ongoing — see `CLAUDE.md`'s "Known
migration debt" notes.

## More documentation

`CLAUDE.md` has the full architecture write-up: the request lifecycle, the multi-project/config
layering model, the routing system's internals, and the consuming-app deployment/security model.
