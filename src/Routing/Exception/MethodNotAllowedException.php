<?php

namespace Core\Routing\Exception;

use Core\Utils\Exception;

/**
 * Class MethodNotAllowedException
 * The path matches a route, but not for the requested HTTP method (405).
 */
class MethodNotAllowedException extends Exception
{

    /**
     * @param array<string> $allowedMethods
     */
    public function __construct(string $path, public readonly array $allowedMethods)
    {
        parent::__construct(
            "The path <em>" . $path . "</em> doesn't allow this method. Allowed: " . implode(', ', $allowedMethods),
            405
        );
    }

}
