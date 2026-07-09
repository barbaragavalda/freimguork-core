<?php

namespace Core\Tests\Fixtures\Container;

/**
 * fixture: nullable-but-typed constructor dependency - should still be
 * autowired to a real instance rather than left as null
 */
class OptionalEngine
{

    public function __construct(public readonly ?Engine $engine = null)
    {
    }

}
