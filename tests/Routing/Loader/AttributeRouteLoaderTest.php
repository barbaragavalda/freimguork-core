<?php

namespace Core\Tests\Routing\Loader;

use Core\Routing\Loader\AttributeRouteLoader;
use PHPUnit\Framework\TestCase;

class AttributeRouteLoaderTest extends TestCase
{

    private const string NAMESPACE = 'Core\\Tests\\Fixtures';

    private function fixturesDirectory(): string
    {
        return __DIR__ . '/../../Fixtures/Controller';
    }

    public function testScansControllersAndSkipsAbstractOnes(): void
    {
        $routes = (new AttributeRouteLoader())->load(self::NAMESPACE, $this->fixturesDirectory());

        $names = array_map(static fn ($route) => $route->name, $routes->all());
        sort($names);

        $this->assertSame(array('blog.index', 'blog.show', 'blog.update', 'home'), $names);
        $this->assertNull($routes->getByName('base.index'));
    }

    public function testClassLevelRouteActsAsPrefix(): void
    {
        $routes = (new AttributeRouteLoader())->load(self::NAMESPACE, $this->fixturesDirectory());

        $this->assertSame('/blog', $routes->getByName('blog.index')->path);
    }

    public function testMissingDirectoryReturnsEmptyCollection(): void
    {
        $routes = (new AttributeRouteLoader())->load(self::NAMESPACE, '/no/such/directory');

        $this->assertSame(0, $routes->count());
    }

    public function testWritesAndReusesCacheFile(): void
    {
        $cacheFile = sys_get_temp_dir() . '/freimguork-route-cache-' . uniqid() . '.php';
        $this->assertFileDoesNotExist($cacheFile);

        try {
            $loader = new AttributeRouteLoader();

            $fromScan = $loader->load(self::NAMESPACE, $this->fixturesDirectory(), $cacheFile);
            $this->assertFileExists($cacheFile);

            // now points to a directory with no controllers at all: if the
            // cache weren't reused, this would come back empty
            $fromCache = $loader->load(self::NAMESPACE, '/no/such/directory', $cacheFile);

            $this->assertSame($fromScan->count(), $fromCache->count());
            $this->assertNotNull($fromCache->getByName('blog.index'));
        } finally {
            @unlink($cacheFile);
        }
    }

}
