<?php

namespace Core\Tests\Model;

use Core\Model\Paginated;
use Core\Tests\Fixtures\Model\ConcretePaginated;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Paginated::__construct() -> parent::__construct() pulls in a real DB connection via
 * Core\Model\Model's defaults, and it isn't forwarded a PDO to inject - built via
 * reflection (no constructor call) instead, setting only the private/protected state
 * getItemsPage()/paginate() actually read.
 */
class PaginatedTest extends TestCase
{

    private function make(array $items, int $page, int $itemsPerPage = 25, int $visiblePages = 4): Paginated
    {
        $reflection = new ReflectionClass(ConcretePaginated::class);
        $paginated  = $reflection->newInstanceWithoutConstructor();
        foreach (array('items' => $items, 'page' => $page, 'itemsPerPage' => $itemsPerPage) as $name => $value) {
            $reflection->getProperty($name)->setValue($paginated, $value);
        }
        // private on the parent class - not visible via the subclass's own ReflectionClass
        (new ReflectionClass(Paginated::class))->getProperty('visiblePages')->setValue($paginated, $visiblePages);
        return $paginated;
    }

    public function testGetItemsPageReturnsTheSliceForTheCurrentPage(): void
    {
        $paginated = $this->make(range(1, 25), page: 1, itemsPerPage: 10);

        $this->assertSame(range(11, 20), $paginated->getItemsPage());
    }

    public function testGetItemsPageIsEmptyWithNoItems(): void
    {
        $paginated = $this->make(array(), page: 0);

        $this->assertSame(array(), $paginated->getItemsPage());
    }

    public function testPaginateReturnsNothingWhenEverythingFitsOnOnePage(): void
    {
        $paginated = $this->make(range(1, 5), page: 0, itemsPerPage: 10);

        $this->assertSame(array(), $paginated->paginate());
    }

    public function testPaginateShowsTheTrailingPageAndEllipsisNearTheStart(): void
    {
        // page 0 (1st, 0-indexed), 20 pages total, 4 visible
        $paginated = $this->make(range(1, 200), page: 0, itemsPerPage: 10, visiblePages: 4);

        $result = $paginated->paginate();

        $this->assertSame(1, $result['current']);
        $this->assertSame(array(1, 2, 3, 4, 5, '...', 20), $result['pages']);
    }

    public function testPaginateShowsTheLeadingPageAndEllipsisNearTheEnd(): void
    {
        // last page (0-indexed 19) of 20, 4 visible
        $paginated = $this->make(range(1, 200), page: 19, itemsPerPage: 10, visiblePages: 4);

        $result = $paginated->paginate();

        $this->assertSame(20, $result['current']);
        $this->assertSame(array(1, '...', 16, 17, 18, 19, 20), $result['pages']);
    }

    public function testPaginateShowsBothEllipsesInTheMiddle(): void
    {
        // page 10 (0-indexed) of 20, 4 visible
        $paginated = $this->make(range(1, 200), page: 9, itemsPerPage: 10, visiblePages: 4);

        $result = $paginated->paginate();

        $this->assertSame(10, $result['current']);
        $this->assertSame(array(1, '...', 8, 9, 10, 11, 12, '...', 20), $result['pages']);
    }

}
