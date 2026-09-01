<?php

namespace Core\Tests\Routing\Loader;

use Core\Routing\Loader\AttributeRouteLoader;
use PHPUnit\Framework\TestCase;

class AttributeRouteLoaderTest extends TestCase
{

    private const string NAMESPACE = 'Core\\Tests\\Fixtures';

    private string|false $originalLocale;

    private string|false $originalTextDomain;

    private string|false $originalLanguage;

    protected function setUp(): void
    {
        $this->originalLocale     = setlocale(LC_ALL, 0);
        $this->originalTextDomain = textdomain(null);
        $this->originalLanguage   = getenv('LANGUAGE');
    }

    protected function tearDown(): void
    {
        setlocale(LC_ALL, $this->originalLocale);
        textdomain($this->originalTextDomain);
        if ($this->originalLanguage === false) {
            putenv('LANGUAGE');
        } else {
            putenv('LANGUAGE=' . $this->originalLanguage);
        }
    }

    private function fixturesDirectory(): string
    {
        return __DIR__ . '/../../Fixtures/Controller';
    }

    public function testScansControllersAndSkipsAbstractOnes(): void
    {
        $routes = (new AttributeRouteLoader())->load(self::NAMESPACE, $this->fixturesDirectory());

        $names = array_map(static fn ($route) => $route->name, $routes->all());
        sort($names);

        $this->assertSame(
            array('blog.index', 'blog.show', 'blog.update', 'home', 'single.home', 'translated.show'),
            $names
        );
        $this->assertNull($routes->getByName('base.index'));
    }

    public function testClassLevelOnlyRouteDispatchesToBuild(): void
    {
        $routes = (new AttributeRouteLoader())->load(self::NAMESPACE, $this->fixturesDirectory());

        $route = $routes->getByName('single.home');
        $this->assertNotNull($route);
        $this->assertSame('/single-home', $route->path);
        $this->assertSame('build', $route->action);
        $this->assertSame(
            'Core\\Tests\\Fixtures\\Controller\\SingleAction\\HomeController',
            $route->controllerClass
        );
    }

    public function testClassLevelRouteActsAsPrefix(): void
    {
        $routes = (new AttributeRouteLoader())->load(self::NAMESPACE, $this->fixturesDirectory());

        $this->assertSame('/blog', $routes->getByName('blog.index')->path);
    }

    public function testStaticSegmentsPassThroughUnchangedWithNoTranslationBound(): void
    {
        $routes = (new AttributeRouteLoader())->load(self::NAMESPACE, $this->fixturesDirectory());

        $this->assertSame('/recepta/{uri}', $routes->getByName('translated.show')->path);
    }

    public function testStaticSegmentsAreTranslatedWhenATranslationExists(): void
    {
        if (shell_exec('which msgfmt') === null) {
            $this->markTestSkipped('msgfmt is not available to compile a test .mo file');
        }

        $localeDir = sys_get_temp_dir() . '/freimguork-locale-test-' . uniqid();
        $domain    = 'routing_test_' . uniqid();
        $moDir     = $localeDir . '/en_US.UTF-8/LC_MESSAGES';
        mkdir($moDir, 0777, true);

        $poFile = $localeDir . '/messages.po';
        file_put_contents($poFile, <<<PO
            msgid ""
            msgstr ""
            "Content-Type: text/plain; charset=UTF-8\\n"

            msgid "recepta"
            msgstr "recipe"
            PO);
        exec('msgfmt ' . escapeshellarg($poFile) . ' -o ' . escapeshellarg($moDir . '/' . $domain . '.mo'));

        try {
            // gettext prefers LANGUAGE over LC_ALL when both are set - Composer sets
            // LANGUAGE=C for its own scripts, which silently defeats setlocale() below
            putenv('LANGUAGE=en_US.UTF-8');
            putenv('LC_ALL=en_US.UTF-8');
            setlocale(LC_ALL, 'en_US.UTF-8', 'en_US');
            bindtextdomain($domain, $localeDir);
            bind_textdomain_codeset($domain, 'UTF-8');
            textdomain($domain);

            $routes = (new AttributeRouteLoader())->load(self::NAMESPACE, $this->fixturesDirectory());

            // static segment translated, {uri} param segment left untouched
            $this->assertSame('/recipe/{uri}', $routes->getByName('translated.show')->path);
        } finally {
            unlink($moDir . '/' . $domain . '.mo');
            rmdir($moDir);
            rmdir(dirname($moDir));
            unlink($poFile);
            rmdir($localeDir);
        }
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
