<?php

namespace Core\Routing;

/**
 * Class RouteMatch
 * Result of a successful Router::match() call.
 */
final class RouteMatch
{

    /**
     * @param array<string, string> $params
     */
    public function __construct(
        public readonly string $controllerClass,
        public readonly string $action,
        public readonly array $params,
        public readonly string $routeName
    ) {
    }

}
