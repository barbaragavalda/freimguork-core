<?php

namespace Core\Tests\Fixtures\Container;

/**
 * fixture: required scalar constructor parameter with no default - cannot be
 * autowired and must fail loudly
 */
class Broken
{

    public function __construct(public readonly string $id)
    {
    }

}
