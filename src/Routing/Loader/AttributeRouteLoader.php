<?php

namespace Core\Routing\Loader;

use Core\Controller\Controller;
use Core\Routing\Attribute\Route as RouteAttribute;
use Core\Routing\Route;
use Core\Routing\RouteCollection;
use FilesystemIterator;
use ReflectionClass;
use ReflectionMethod;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Class AttributeRouteLoader
 * Scans a controller directory for #[Route] attributes and builds a RouteCollection.
 *
 * Pure w.r.t. globals/constants: callers decide the directory to scan and,
 * optionally, a cache file to read from / write to (e.g. Bootstrap picks the
 * cache path following the IS_DEV/DIR_ROOT convention; tests can pass a
 * fixtures directory and a temp cache path, or none at all).
 */
class AttributeRouteLoader
{

    /**
     * @param string      $namespace base namespace of the controllers (e.g. "Web")
     * @param string      $directory directory to scan (e.g. ".../src/Web/Controller/")
     * @param string|null $cacheFile if given and it exists, loaded instead of scanning;
     *                                if given and it doesn't exist, written after scanning
     */
    public function load(string $namespace, string $directory, ?string $cacheFile = null): RouteCollection
    {
        if ($cacheFile !== null && file_exists($cacheFile)) {
            return RouteCollection::fromArray(include $cacheFile);
        }

        $collection = $this->scan($namespace, $directory);

        if ($cacheFile !== null) {
            $this->writeCache($cacheFile, $collection);
        }

        return $collection;
    }

    private function scan(string $namespace, string $directory): RouteCollection
    {
        $collection = new RouteCollection();

        foreach ($this->findControllerClasses($namespace, $directory) as $class) {
            $this->addRoutesForClass($collection, $class);
        }

        return $collection;
    }

    /**
     * @return array<class-string>
     */
    private function findControllerClasses(string $namespace, string $directory): array
    {
        if (!is_dir($directory)) {
            return array();
        }

        $classes = array();
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $relative = substr($file->getPathname(), strlen(rtrim($directory, '/\\')) + 1);
            $relative = str_replace(array('/', '\\', '.php'), array('\\', '\\', ''), $relative);
            $class    = $namespace . '\\Controller\\' . $relative;

            if (class_exists($class) && is_subclass_of($class, Controller::class)) {
                $classes[] = $class;
            }
        }

        return $classes;
    }

    /**
     * @param class-string $class
     */
    private function addRoutesForClass(RouteCollection $collection, string $class): void
    {
        $reflection = new ReflectionClass($class);
        if ($reflection->isAbstract()) {
            return;
        }

        $prefixes = array_map(
            static fn ($attribute) => $attribute->newInstance(),
            $reflection->getAttributes(RouteAttribute::class)
        );
        if (empty($prefixes)) {
            $prefixes = array(new RouteAttribute());
        }

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() !== $class) {
                continue;
            }

            foreach ($method->getAttributes(RouteAttribute::class) as $attributeRef) {
                $attribute = $attributeRef->newInstance();
                foreach ($prefixes as $prefix) {
                    $path         = rtrim($prefix->path, '/') . '/' . ltrim($attribute->path, '/');
                    $requirements = array_merge($prefix->requirements, $attribute->requirements);

                    $collection->add(
                        Route::compile(
                            $path,
                            $attribute->methods,
                            $class,
                            $method->getName(),
                            $attribute->name,
                            $requirements
                        )
                    );
                }
            }
        }
    }

    private function writeCache(string $cacheFile, RouteCollection $collection): void
    {
        $dir = dirname($cacheFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        file_put_contents(
            $cacheFile,
            '<?php return ' . var_export($collection->toArray(), true) . ';' . PHP_EOL
        );
    }

}
