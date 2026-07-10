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

The framework is ~10 years old and is being modernized in stages. Routing was rewritten from
scratch as a clean-break v2 (attribute-based, HTTP-method-aware, unit-tested). Dependency Injection
followed as its own phase: a PSR-11 `Core\Container\Container` with `Bootstrap` as the composition
root, `Controller`/`CacheManager` taking required constructor dependencies, and `Model` deliberately
kept on optional dependencies (see `CLAUDE.md`'s "Dependency Injection" section for why). The
response layer is PSR-7 now too — controllers return a `Psr\Http\Message\ResponseInterface`
instead of echoing/setting headers directly, with `Bootstrap` as the single point that emits it
(see `CLAUDE.md`'s "Views" section). Static analysis (PHPStan, level 5) is now configured too, with
a baseline covering what wasn't fixed when it was introduced (mostly `Model/File.php`'s GD
image-handling code and the `Model/Push/*` push notification classes). Broader test coverage is
ongoing — most of `Core\Model\*`/`Core\Utils\Language` still isn't unit-tested. Not every consuming
app has migrated to the new routing or the new controller constructors yet — see `CLAUDE.md`'s
"Known migration debt" notes for current status.

## More documentation

`CLAUDE.md` has the full architecture write-up: the request lifecycle, the multi-project/config
layering model, the routing system's internals, and the consuming-app deployment/security model.
