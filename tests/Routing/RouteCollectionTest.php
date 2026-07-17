<?php

namespace Core\Tests\Routing;

use Core\Routing\Route;
use Core\Routing\RouteCollection;
use PHPUnit\Framework\TestCase;

class RouteCollectionTest extends TestCase
{

    public function testAddAndGetByName(): void
    {
        $route = Route::compile('/blog/{slug}', array('GET'), 'App\\Controller\\Blog', 'show', 'blog.show');

        $collection = new RouteCollection();
        $collection->add($route);

        $this->assertSame(1, $collection->count());
        $this->assertSame($route, $collection->getByName('blog.show'));
        $this->assertNull($collection->getByName('unknown'));
        $this->assertSame(array($route), iterator_to_array($collection->getIterator()));
    }

    public function testAddAllMergesRoutesKeepingOriginalOrderFirst(): void
    {
        $ownRoute    = Route::compile('/register', array('POST'), 'Api\\Controller\\Register', 'build', 'api.register');
        $vendorRoute = Route::compile('/login', array('POST'), 'Webservice\\Controller\\Login', 'build', 'webservice.login');

        $collection = new RouteCollection();
        $collection->add($ownRoute);

        $vendorCollection = new RouteCollection();
        $vendorCollection->add($vendorRoute);

        $collection->addAll($vendorCollection);

        $this->assertSame(2, $collection->count());
        $this->assertSame(array($ownRoute, $vendorRoute), $collection->all());
        $this->assertSame($vendorRoute, $collection->getByName('webservice.login'));
    }

    public function testToArrayFromArrayRoundTrip(): void
    {
        $collection = new RouteCollection();
        $collection->add(Route::compile('/blog', array('GET'), 'App\\Controller\\Blog', 'index', 'blog.index'));
        $collection->add(
            Route::compile(
                '/blog/{id}',
                array('POST'),
                'App\\Controller\\Blog',
                'update',
                'blog.update',
                array('id' => '\d+')
            )
        );

        $restored = RouteCollection::fromArray($collection->toArray());

        $this->assertSame($collection->count(), $restored->count());
        $this->assertEquals($collection->getByName('blog.update'), $restored->getByName('blog.update'));
    }

}
