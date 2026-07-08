<?php

namespace Core\Routing;

use Core\Routing\Exception\MethodNotAllowedException;
use Core\Routing\Exception\RouteNotFoundException;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class Router
 * Matches an incoming PSR-7 request against a RouteCollection, and generates
 * URLs back from route names (reverse routing).
 */
class Router
{

    /**
     * @var ?Router holds the router of the currently matched project, so it's
     *              reachable from the Twig extension for path()/url() reverse
     *              routing without a full DI container (a stopgap for the
     *              upcoming DI phase, kept consistent with the Config/Session
     *              singleton-style access already used throughout the framework)
     */
    private static ?Router $current = null;

    public function __construct(private readonly RouteCollection $routes)
    {
    }

    public static function setCurrent(self $router): void
    {
        self::$current = $router;
    }

    public static function getCurrent(): ?self
    {
        return self::$current;
    }

    /**
     * @throws RouteNotFoundException      no route matches the path at all
     * @throws MethodNotAllowedException   a route matches the path, but not this HTTP method
     */
    public function match(ServerRequestInterface $request): RouteMatch
    {
        $path   = RouteCompiler::normalizePath($request->getUri()->getPath());
        $method = strtoupper($request->getMethod());

        $allowedMethods = array();

        foreach ($this->routes as $route) {
            if (preg_match($route->regex, $path, $matches) !== 1) {
                continue;
            }

            if (!in_array($method, $route->methods, true)) {
                $allowedMethods = array_unique(array_merge($allowedMethods, $route->methods));
                continue;
            }

            $params = array();
            foreach ($route->paramNames as $paramName) {
                if (isset($matches[ $paramName ]) && $matches[ $paramName ] !== '') {
                    $params[ $paramName ] = $matches[ $paramName ];
                }
            }

            return new RouteMatch($route->controllerClass, $route->action, $params, $route->name);
        }

        if (count($allowedMethods)) {
            throw new MethodNotAllowedException($path, $allowedMethods);
        }

        throw new RouteNotFoundException($path);
    }

    /**
     * @param array<string, string|int> $params
     *
     * @throws RouteNotFoundException no route is registered under this name
     */
    public function generate(string $name, array $params = array()): string
    {
        $route = $this->routes->getByName($name);
        if ($route === null) {
            throw new RouteNotFoundException($name);
        }

        $path = $route->path;
        foreach ($params as $key => $value) {
            $path = str_replace(array('{' . $key . '}', '{' . $key . '?}'), (string) $value, $path);
        }
        $path = preg_replace('/\{\w+\?\}/', '', $path);

        return RouteCompiler::normalizePath($path);
    }

}
