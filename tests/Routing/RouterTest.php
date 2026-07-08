<?php

namespace Core\Tests\Routing;

use Core\Routing\Exception\MethodNotAllowedException;
use Core\Routing\Exception\RouteNotFoundException;
use Core\Routing\Route;
use Core\Routing\RouteCollection;
use Core\Routing\Router;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{

    private function router(): Router
    {
        $routes = new RouteCollection();
        $routes->add(Route::compile('/blog', array('GET'), 'App\\Controller\\Blog', 'index', 'blog.index'));
        $routes->add(
            Route::compile(
                '/blog/{slug}',
                array('GET'),
                'App\\Controller\\Blog',
                'show',
                'blog.show',
                array('slug' => '[a-z0-9-]+')
            )
        );
        $routes->add(
            Route::compile(
                '/blog/{id}',
                array('POST'),
                'App\\Controller\\Blog',
                'update',
                'blog.update',
                array('id' => '\d+')
            )
        );

        return new Router($routes);
    }

    public function testMatchesStaticPath(): void
    {
        $match = $this->router()->match(new ServerRequest('GET', '/blog'));

        $this->assertSame('App\\Controller\\Blog', $match->controllerClass);
        $this->assertSame('index', $match->action);
        $this->assertSame(array(), $match->params);
        $this->assertSame('blog.index', $match->routeName);
    }

    public function testMatchesDynamicPathAndExtractsParams(): void
    {
        $match = $this->router()->match(new ServerRequest('GET', '/blog/hello-world'));

        $this->assertSame('show', $match->action);
        $this->assertSame(array('slug' => 'hello-world'), $match->params);
    }

    public function testDifferentMethodOnSamePathMatchesDifferentAction(): void
    {
        $match = $this->router()->match(new ServerRequest('POST', '/blog/42'));

        $this->assertSame('update', $match->action);
        $this->assertSame(array('id' => '42'), $match->params);
    }

    public function testUnknownPathThrowsNotFound(): void
    {
        $this->expectException(RouteNotFoundException::class);
        $this->router()->match(new ServerRequest('GET', '/does-not-exist'));
    }

    public function testKnownPathWrongMethodThrowsMethodNotAllowed(): void
    {
        try {
            $this->router()->match(new ServerRequest('DELETE', '/blog'));
            $this->fail('Expected MethodNotAllowedException was not thrown');
        } catch (MethodNotAllowedException $e) {
            $this->assertSame(array('GET'), $e->allowedMethods);
        }
    }

    public function testGenerateBuildsPathFromRouteName(): void
    {
        $this->assertSame('/blog', $this->router()->generate('blog.index'));
        $this->assertSame('/blog/hello-world', $this->router()->generate('blog.show', array('slug' => 'hello-world')));
    }

    public function testGenerateUnknownNameThrowsNotFound(): void
    {
        $this->expectException(RouteNotFoundException::class);
        $this->router()->generate('unknown.route');
    }

}
