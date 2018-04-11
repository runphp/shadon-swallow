<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Plugin;

/**
 * 数据库监听器
 * 
 * @author    范世军<fanshijun@eelly.net>
 * @since     2015年9月16日
 * @version   1.0
 */
class Query extends \Swallow\Di\Injectable
{

    /**
     * 如果事件触发器是'beforeQuery'，此函数将会被执行
     */
    public function beforeQuery($event, $connection, $sql)
    {
        $statement = $connection->getSQLStatement();
        $sqloperator = strtolower(substr($statement, 0, stripos($statement, ' ')));
        $masteroperator = array('insert', 'update', 'delete', 'replace');
        if(in_array($sqloperator, $masteroperator) && !preg_match('/ecm_table_sync/i', $statement)){
            preg_match_all('/ecm_\w+/i', $statement, $matchs);
            $table = array_unique($matchs[0]);
            if(!empty($table)){
                foreach ($table as $val){
                    $connection->query("replace into ecm_table_sync value('".md5($val)."', UNIX_TIMESTAMP())");
                }
            }
        }
        if(preg_match('/ecm_table_sync/i', $statement)){
            return ;
        }
        //$connection->getDescriptor(); //链接信息
        
        if(APP_DEBUG){
            //sql验证
            $descriptor = $connection->getDescriptor();
            $prefix = isset($descriptor['prefix']) ? $descriptor['prefix'] : 'ecm_';
            $verify = $this->getDI()->getShared('\Swallow\Debug\VerifySql');
            $verify->verify($statement, $prefix);
            //sql执行时间
            $this->getProfiler()->startProfile($statement, $connection->getSQLVariables(), $connection->getSQLBindTypes());
        }
    }

    /**
     * 如果事件触发器是'afterQuery'，此函数将会被执行
     */
    public function afterQuery($event, $connection, $data)
    {
        $statement = $connection->getSQLStatement();
        if(preg_match('/ecm_table_sync/i', $statement)){
            return ;
        }
        APP_DEBUG && $this->getProfiler()->stopProfile();
    }

    /**
     * 获取Profiler
     * 
     * @return \Swallow\Db\Profiler
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年9月16日
     */
    public function getProfiler()
    {
        return $this->getDI()->getShared('Phalcon\Db\Profiler');
    }
}