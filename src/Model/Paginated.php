<?php

namespace Core\Model;

abstract class Paginated extends Model {

    const VISIBLE_PAGES = 4;

    private $itemsPerPage = 25;

    /**
     * @var int     current page
     */
    protected $page = 0;

    /**
     * @var array   all items
     */
    protected $items = array();

    /**
     * @var array   list filters selected by user
     */
    protected $filters = array();

    public function __construct($page, $itemsPerPage = 25, $autoInit = true){
        parent::__construct();

        $this->itemsPerPage = $itemsPerPage;

        if( $page && $page > 0 ){
            $this->page = $page - 1;
        }

        if( $autoInit ) $this->initAll();
    }

    public function getFilters(){
        return $this->filters;
    }

    public function setFilters($filters){
        $this->filters = $filters;
    }

    /**
     * load all items without pagination
     * @return array
     */
    abstract protected function initAll();

    /**
     * get all items on pagination
     * @return array
     */
    public function getAll(){
        return $this->items;
    }

    /**
     * return only items on current page
     * @return array
     */
    public function getItemsPage(){
        if( count($this->items) ){
            $start = $this->page * $this->itemsPerPage;
            $length = $this->itemsPerPage;
            return array_slice($this->items, $start, $length);
        }
        return array();
    }

    /**
     * create pagination structure
     * @return array|void   pagination
     */
    public function paginate(){
        $currentPage = $this->page + 1;
        $itemCount = count($this->items);
        $firstPage = 1;
        $lastPage  = ceil($itemCount / $this->itemsPerPage);
        if( $lastPage == 1 || $this->page > $lastPage  ){
            // no pagination
            return;
        }

        // set up
        if ($currentPage <= self::VISIBLE_PAGES) {
            $firstAdjacentPage = $firstPage;
            $lastAdjacentPage  = min($firstPage + self::VISIBLE_PAGES, $lastPage);
        } elseif ($currentPage > $lastPage - self::VISIBLE_PAGES) {
            $lastAdjacentPage  = $lastPage;
            $firstAdjacentPage = $lastPage - self::VISIBLE_PAGES;
        } else {
            $firstAdjacentPage = $currentPage - self::VISIBLE_PAGES/2;
            $lastAdjacentPage  = $currentPage + self::VISIBLE_PAGES/2;
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
            'pages' => $pagination,
            'current' => $this->page + 1
        );
    }
}