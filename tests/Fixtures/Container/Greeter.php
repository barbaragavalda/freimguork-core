<?php

namespace Core\Tests\Fixtures\Container;

/**
 * fixture: scalar constructor parameter with a default value
 */
class Greeter
{

    public function __construct(public readonly string $name = 'World')
    {
    }

}
