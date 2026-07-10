<?php

namespace Core\Tests\View\Extension;

use Core\Routing\Route;
use Core\Routing\RouteCollection;
use Core\Routing\Router;
use Core\Utils\Config;
use Core\View\Extension\Twig;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Twig receives Router/Config via real constructor injection (see
 * Core\Bootstrap - Controller::getHTML() and Response\HTML::initResponse()
 * are its two composition points), which is what makes it testable at all
 * without a running request.
 */
class TwigTest extends TestCase
{

    public function testPathReturnsEmptyStringWhenNoRouterIsAvailable(): void
    {
        $twig = new Twig(null, $this->config('https://example.com/'));

        $this->assertSame('', $twig->path('blog.index'));
    }

    public function testPathDelegatesToTheRouter(): void
    {
        $twig = new Twig($this->router(), $this->config('https://example.com/'));

        $this->assertSame('/blog', $twig->path('blog.index'));
    }

    public function testUrlCombinesTheConfiguredDomainWithThePath(): void
    {
        $twig = new Twig($this->router(), $this->config('https://example.com/'));

        $this->assertSame('https://example.com/blog', $twig->url('blog.index'));
    }

    public function testUrlTrimsTheDomainsTrailingSlashBeforeAppendingThePath(): void
    {
        // getDomain() includes a trailing slash (e.g. "https://example.com/ca/")
        // - url() must not end up with a double slash before the path
        $twig = new Twig($this->router(), $this->config('https://example.com/ca/'));

        $this->assertSame('https://example.com/ca/blog', $twig->url('blog.index'));
    }

    public function testUrlIsJustTheDomainWhenNoRouterIsAvailable(): void
    {
        $twig = new Twig(null, $this->config('https://example.com/'));

        $this->assertSame('https://example.com', $twig->url('blog.index'));
    }

    private function router(): Router
    {
        $routes = new RouteCollection();
        $routes->add(Route::compile('/blog', array('GET'), 'App\\Controller\\Blog', 'index', 'blog.index'));

        return new Router($routes);
    }

    /**
     * Config has a private constructor (singleton via getInstance()) - since
     * Twig no longer calls Config::getInstance() itself (that's the point of
     * this change), a real Config instance can be built directly via
     * reflection instead, entirely independent of the static singleton.
     */
    private function config(string $domain): Config
    {
        $config = (new ReflectionClass(Config::class))->newInstanceWithoutConstructor();
        $config->setDomains(array('app' => $domain, 'static' => $domain));

        return $config;
    }

}
