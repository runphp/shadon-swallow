<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Debug;

/**
 * Debug 模式,校验代码规范
 * 
 * @author    liaochu<liaochu@eelly.net>
 * @since     2016-7-20
 * @version   1.0
 */
class Query extends \Swallow\Plugin\Query
{
    private $_transactionSql = '';
    
    private $_startTransaction = false;

    public function afterQuery($event, $connection, $data)
    {
        $statement = $connection->getSQLStatement();
        if(preg_match('/ecm_table_sync/i', $statement)){
            return ;
        }
        $this->_transactionSql[] = $statement;
    }
    
    /**
     * 事务开始
     * 
     * 
     * @param unknown $event
     * @param unknown $connection
     * @param unknown $data
     * @author liaochu<liaochu@eelly.net>
     * @since  2016-7-20
     */
    public function beginTransaction($event, $connection, $data)
    {
        $this->_transactionSql = [];
        $this->_startTransaction = true;
    }
    
    /**
     * 提交事务
     * 
     * 
     * @param unknown $event
     * @param unknown $connection
     * @param unknown $data
     * @author liaochu<liaochu@eelly.net>
     * @since  2016-7-20
     */
    public function commitTransaction($event, $connection, $data)
    {       
        $this->_startTransaction = false;
        $this->detectSelect();
    }
    
    protected function detectSelect()
    {
        $times = 0;
        $tmp = [];
        print_r($this->_transactionSql);
    	foreach ($this->_transactionSql as $sql) {
    		if (preg_match('/^select/i', $sql)) {
    		    $tmp[] = $sql;
    			$times++;
    		}
    	}
    	
    	if ($times > 3) {
            throw new \Exception('事务中存在大量查询,' . implode(';', $tmp));
    	}
    }
}