<?php

namespace Core\Tests\Fixtures\Container;

/**
 * fixture: required class-typed constructor dependency
 */
class Car
{

    public function __construct(public readonly Engine $engine)
    {
    }

}
