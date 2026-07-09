<?php

namespace Core\Container;

use Core\Container\Exception\ContainerException;
use Core\Container\Exception\NotFoundException;
use Psr\Container\ContainerInterface;

/**
 * Class Container
 * Minimal PSR-11 autowiring container.
 *
 * Only ids registered via {@see instance()}/{@see singleton()} are shared. An
 * id with no explicit binding is autowired fresh on every {@see get()}/
 * {@see make()} call - this matters for classes like Core\Controller\CacheManager,
 * which must never end up silently shared just because they were autowired.
 */
class Container implements ContainerInterface
{

    /**
     * @var ?Container holds the container of the current request, so classes
     *                 that can't reach it through their own constructor (e.g.
     *                 static facades like Core\Routing\Router::getCurrent())
     *                 can still resolve against it
     */
    private static ?Container $current = null;

    /** @var array<string, object> */
    private array $instances = array();

    /** @var array<string, \Closure> */
    private array $factories = array();

    public function __construct()
    {
        $this->instances[self::class]            = $this;
        $this->instances[ContainerInterface::class] = $this;
    }

    public static function setCurrent(?self $container): void
    {
        self::$current = $container;
    }

    public static function getCurrent(): ?self
    {
        return self::$current;
    }

    /**
     * registers an already-built object under $id (shared)
     */
    public function instance(string $id, object $instance): void
    {
        $this->instances[ $id ] = $instance;
        unset($this->factories[ $id ]);
    }

    /**
     * registers a lazy factory under $id, memoized on first get() (shared)
     */
    public function singleton(string $id, \Closure $factory): void
    {
        $this->factories[ $id ] = $factory;
        unset($this->instances[ $id ]);
    }

    public function has(string $id): bool
    {
        return $this->isBound($id) || class_exists($id) || interface_exists($id);
    }

    /**
     * explicitly-bound ids resolve to the shared instance; anything else that
     * is a real class name is autowired fresh (not memoized)
     *
     * @throws NotFoundException
     * @throws ContainerException
     */
    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[ $id ];
        }

        if (isset($this->factories[ $id ])) {
            return $this->instances[ $id ] = ($this->factories[ $id ])($this);
        }

        if (class_exists($id) || interface_exists($id)) {
            return $this->build($id);
        }

        throw new NotFoundException("No entry was found for '$id'");
    }

    /**
     * always builds a fresh, non-memoized instance via constructor autowiring -
     * same resolution as get() on an unbound class, named separately for
     * call-site clarity ("build me one of these")
     *
     * @param array<string, mixed> $parameters explicit overrides, keyed by
     *                                          constructor parameter name
     *
     * @throws NotFoundException
     * @throws ContainerException
     */
    public function make(string $class, array $parameters = array()): object
    {
        return $this->build($class, $parameters);
    }

    private function isBound(string $id): bool
    {
        return array_key_exists($id, $this->instances) || isset($this->factories[ $id ]);
    }

    /**
     * @throws NotFoundException
     * @throws ContainerException
     */
    private function build(string $class, array $parameters = array()): object
    {
        if (!class_exists($class) && !interface_exists($class)) {
            throw new NotFoundException("Class '$class' does not exist");
        }

        $reflection = new \ReflectionClass($class);
        if (!$reflection->isInstantiable()) {
            throw new ContainerException("Class '$class' is not instantiable");
        }

        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            return $reflection->newInstance();
        }

        $arguments = array();
        foreach ($constructor->getParameters() as $parameter) {
            $arguments[] = $this->resolveParameter($class, $parameter, $parameters);
        }

        return $reflection->newInstanceArgs($arguments);
    }

    /**
     * @throws ContainerException
     */
    private function resolveParameter(string $class, \ReflectionParameter $parameter, array $overrides): mixed
    {
        $name = $parameter->getName();
        if (array_key_exists($name, $overrides)) {
            return $overrides[ $name ];
        }

        $type = $parameter->getType();
        if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
            $dependency = $type->getName();
            try {
                return $this->isBound($dependency) ? $this->get($dependency) : $this->build($dependency);
            } catch (NotFoundException|ContainerException $e) {
                if (!$parameter->isDefaultValueAvailable() && !$type->allowsNull()) {
                    throw $e;
                }
            }
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        if ($type === null || $type->allowsNull()) {
            return null;
        }

        throw new ContainerException(
            "Cannot resolve parameter '\$$name' for class '$class'"
        );
    }

}
