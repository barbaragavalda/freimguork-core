<?php

namespace Core\Tests\Fixtures\Model;

use Core\Model\Paginated;

/**
 * Paginated is abstract only because of initAll() - this fills it in with a no-op so
 * PaginatedTest can build an instance via reflection (skipping the constructor, which
 * pulls in a real DB connection via Model's defaults).
 */
class ConcretePaginated extends Paginated
{

    public function initAll(): void
    {
    }

}
