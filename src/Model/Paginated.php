<?php

namespace Core\Model;

abstract class Paginated extends Model
{

    private int $visiblePages;

    protected int $itemsPerPage;

    /**
     * @var int     current page
     */
    protected int $page = 0;

    /**
     * @var array   all items
     */
    protected array $items = array();

    /**
     * @var array   list filters selected by user
     */
    protected array $filters = array();

    public function __construct(int $page, int $itemsPerPage = 25, bool $autoInit = true, int $visiblePages = 4)
    {
        parent::__construct();

        $this->itemsPerPage = $itemsPerPage;
        $this->visiblePages = $visiblePages;

        if ($page && $page > 0) {
            $this->page = $page - 1;
        }

        if ($autoInit) {
            $this->initAll();
        }
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function setFilters($filters): void
    {
        $this->filters = $filters;
    }

    /**
     * load all items without pagination
     */
    abstract public function initAll(): void;

    /**
     * get all items on pagination
     * @return array
     */
    public function getAll(): array
    {
        return $this->items;
    }

    /**
     * return only items on current page
     * @return array
     */
    public function getItemsPage(): array
    {
        if (count($this->items)) {
            $start  = $this->page * $this->itemsPerPage;
            $length = $this->itemsPerPage;
            return array_slice($this->items, $start, $length);
        }
        return array();
    }

    /**
     * create pagination structure
     * @return array   pagination
     */
    public function paginate(): array
    {
        $currentPage = $this->page + 1;
        $itemCount   = count($this->items);
        $firstPage   = 1;
        $lastPage    = (int) ceil($itemCount / $this->itemsPerPage);
        if ($lastPage == 1 || $this->page > $lastPage) {
            // no pagination
            return array();
        }

        // set up
        if ($currentPage <= $this->visiblePages) {
            $firstAdjacentPage = $firstPage;
            $lastAdjacentPage  = min($firstPage + $this->visiblePages, $lastPage);
        } elseif ($currentPage > $lastPage - $this->visiblePages) {
            $lastAdjacentPage  = $lastPage;
            $firstAdjacentPage = $lastPage - $this->visiblePages;
        } else {
            $firstAdjacentPage = $currentPage - $this->visiblePages / 2;
            $lastAdjacentPage  = $currentPage + $this->visiblePages / 2;
        }

        $pagination = array();
        if ($firstAdjacentPage > $firstPage) {
            $pagination[] = $firstPage;
            if ($firstAdjacentPage > $firstPage + 1) {
                $pagination[] = '...';
            }
        }
        for ($i = $firstAdjacentPage; $i <= $lastAdjacentPage; $i++) {
            $pagination[] = $i;
        }
        if ($lastAdjacentPage < $lastPage) {
            if ($lastAdjacentPage < $lastPage - 1) {
                $pagination[] = '...';
            }
            $pagination[] = $lastPage;
        }

        return array(
            'pages'   => $pagination,
            'current' => $this->page + 1
        );
    }
}