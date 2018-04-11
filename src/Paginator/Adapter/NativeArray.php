<?php

namespace Swallow\Paginator\Adapter;

/**
 * Swallow\Paginator\Adapter\NativeArray
 * Pagination using a PHP array as source of data
 * <code>
 * $paginator = new \Swallow\Paginator\Adapter\NativeArray(
 * array(
 * "data"  => array(
 * array('id' => 1, 'name' => 'Artichoke'),
 * array('id' => 2, 'name' => 'Carrots'),
 * array('id' => 3, 'name' => 'Beet'),
 * array('id' => 4, 'name' => 'Lettuce'),
 * array('id' => 5, 'name' => '')
 * ),
 * "limit" => 5,
 * "page"  => $currentPage
 * "totalRows" => 10
 * )
 * );
 * </code>
 */
class NativeArray extends \Phalcon\Paginator\Adapter\NativeArray
{

    /**
     * Configuration of the paginator
     */
    protected $_config = null;

    /**
     * 总记录数
     * @var int
     */
    protected $_totalRows = 0;

    /**
     * Swallow\Paginator\Adapter\NativeArray constructor
     *
     * @param array $config 
     */
    public function __construct(array $config)
    {
        $this->_config = $config;
        
        //每页显示记录数
        $this->_limitRows = (int) $config['limit'];
        //当前页
        $this->_page = (int) $config['page'];
        //总记录数
        $this->_totalRows = (int) $config['totalRows'];
        
        if ((int) $this->_limitRows < 1) {
            throw new \Exception("Invalid limit for paginator");
        }
    }

    /**
     * Returns a slice of the resultset to show in the pagination
     *
     * @return \stdClass 
     */
    public function getPaginate()
    {
        $config = $this->_config;
        $items = $config["data"];
        
        if (! is_array($items)) {
            throw new \Exception("Invalid data for paginator");
        }
        
        $pageNumber = $this->_page;
        $pageNumber <= 0 && $pageNumber = 1;
        
        $rowcount = $this->_totalRows;
        $totalPages = ceil($rowcount / $this->_limitRows);
        
        $next = $pageNumber < $totalPages ? $pageNumber + 1 : $totalPages;
        
        $before = $pageNumber > 1 ? $pageNumber - 1 : 1;
        
        $items = array_map(function ($item) {
            return (object) $item;
        }, $items);
        $page = new \stdClass();
        $page->items = $items;
        $page->first = 1;
        $page->before = $before;
        $page->current = $pageNumber;
        $page->last = $totalPages;
        $page->next = $next;
        $page->total_pages = $totalPages;
        $page->total_items = $rowcount;
        $page->limit = $this->_limitRows;
        
        return $page;
    }
}

