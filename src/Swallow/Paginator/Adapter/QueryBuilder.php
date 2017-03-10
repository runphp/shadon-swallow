<?php

namespace Swallow\Paginator\Adapter;

/**
 * Phalcon\Paginator\Adapter\QueryBuilder
 * Pagination using a PHQL query builder as source of data
 * <code>
 * $builder = $$this->modelsManager->createBuilder()
 * ->columns('id, name')
 * ->from('Robots')
 * ->orderBy('name');
 * $paginator = new Phalcon\Paginator\Adapter\QueryBuilder(array(
 * "builder" => $builder,
 * "limit"=> 20,
 * "$page" => 1
 * ));
 * </code>
 */
class QueryBuilder extends \Phalcon\Paginator\Adapter\QueryBuilder
{
    /**
     * Returns a slice of the resultset to show in the pagination
     */
    public function getPaginate()
    {
        $originalBuilder = $this->_builder;

        /**
         * We make a copy of the original builder to leave it as it is
         */
         $builder = clone $originalBuilder;

        /**
         * We make a copy of the original builder to count the total of records
         */
         $totalBuilder = clone $builder;

         $limit = $this->_limitRows;
         $numberpage = (int) $this->_page;

        if (!$numberpage) {
             $numberpage = 1;
        }

         $number = $limit * ($numberpage - 1);

        /**
         * Set the limit clause avoiding negative offsets
        */
        if ($number < $limit) {
            $builder->limit($limit);
        } else {
            $builder->limit($limit, $number);
        }

        $this->verifyColumns($builder->getColumns());
        $query = $builder->getQuery();

        if ($numberpage == 1) {
             $before = 1;
        } else {
             $before = $numberpage - 1;
        }

        /**
         * Execute the query an return the requested slice of data
         */
         $items = $query->execute();

        /**
         * Change the queried columns by a COUNT(*)
        */
        $totalBuilder->columns("COUNT(*) [rowcount]");

        /**
         * Remove the 'ORDER BY' clause, PostgreSQL requires $this
        */
        $totalBuilder->orderBy(null);

        /**
         * Obtain the PHQL for the total query
        */
         $totalQuery = $totalBuilder->getQuery();

        /**
         * Obtain the result of the total query
        */
        $result = $totalQuery->execute();
        $row = $result->getFirst();
        $rowcount = intval($row->rowcount);
        $totalpages = intval(ceil($rowcount / $limit));

        if ($numberpage < $totalpages) {
             $next = $numberpage + 1;
        } else {
             $next = $totalpages;
        }
        
        $items = $items->toArray();
        $items = array_map(function ($item) {
            return (object) $item;
        }, $items);
        
        $page = new \stdClass();
        $page->items = $items;
        $page->first = 1;
        $page->before = $before;
        $page->current = $numberpage;
        $page->last = $totalpages;
        $page->next = $next;
        $page->total_pages = $totalpages;
        $page->total_items = $rowcount;
        $page->limit = $this->_limitRows;

        return $page;
    }

    /**
     * Sets the columns to be queried
     * <code>
     * $builder->columns("id, name");
     * </code>
     *
     * @param mixed $columns
     * @return Builder
     */
    public function verifyColumns($columns) {
        if(strstr($columns,'*')){
            throw new \ErrorException('不能使用通配符 * 查询');
        }
        return;
    }
}
