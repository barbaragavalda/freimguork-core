<?php

namespace Core\Routing\Attribute;

use Attribute;

/**
 * Class Route
 * Declares an HTTP route on a controller class (prefix) or method (action).
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class Route
{

    public function __construct(
        public string $path = '',
        public array $methods = array('GET'),
        public ?string $name = null,
        public array $requirements = array()
    ) {
    }

}
