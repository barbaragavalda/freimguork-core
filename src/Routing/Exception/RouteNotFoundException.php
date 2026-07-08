<?php

namespace Core\Routing\Exception;

use Core\Utils\Exception;

/**
 * Class RouteNotFoundException
 * No route matches the requested path (404).
 */
class RouteNotFoundException extends Exception
{

    public function __construct(string $path)
    {
        parent::__construct("No route matches the path <em>" . $path . "</em>", 404);
    }

}
