<?php

namespace Core\Routing;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * Class RouteCollection
 * All the routes belonging to a single resolved Project (sub-application).
 */
class RouteCollection implements IteratorAggregate
{

    /**
     * @var array<Route>
     */
    private array $routes = array();

    /**
     * @var array<string, Route>
     */
    private array $byName = array();

    public function add(Route $route): void
    {
        $this->routes[] = $route;
        $this->byName[ $route->name ] = $route;
    }

    /**
     * @return array<Route>
     */
    public function all(): array
    {
        return $this->routes;
    }

    public function getByName(string $name): ?Route
    {
        return $this->byName[ $name ] ?? null;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->routes);
    }

    public function count(): int
    {
        return count($this->routes);
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function toArray(): array
    {
        return array_map(static fn (Route $route) => $route->toArray(), $this->routes);
    }

    /**
     * @param array<array<string, mixed>> $data
     */
    public static function fromArray(array $data): self
    {
        $collection = new self();
        foreach ($data as $routeData) {
            $collection->add(Route::fromArray($routeData));
        }
        return $collection;
    }

}
