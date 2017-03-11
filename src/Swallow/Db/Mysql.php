<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */

namespace Swallow\Db;

use Phalcon\Di\InjectionAwareInterface;

/**
 * 数据库基类
 *
 * @author     SpiritTeam
 * @since      2015年8月13日
 * @version    1.0
 */
class Mysql extends \Phalcon\Db\Adapter\Pdo\Mysql implements InjectionAwareInterface
{
    protected $di;
    
    /**
     * 重连mysql的尝试次数
     *
     * @var int
     */
    private $reconnectTriedCount = 0;
    
    /**
     * 最大的重连尝试次数
     */
    const RECONNECT_TRIED_MAX = 20;
    
    /**
     * Sets the dependency injector
     *
     * @param mixed $di
     */
    public function setDI(\Phalcon\DiInterface $di)
    {
        $this->di = $di;
        $this->setEventsManager($di->getEventsManager());
    }
    
    /**
     * Returns the internal dependency injector
     *
     * @return \Phalcon\DiInterface
    */
    public function getDI()
    {
        return $this->di;
    }
    
    /**
     * 
     * @param type $sqlStatement
     * @param type $bindParams
     * @param type $bindTypes
     */
    public function query($sqlStatement, $bindParams = null, $bindTypes = null)
    {
        try {
            parent::query($sqlStatement, $bindParams, $bindTypes);
            $this->reconnectTriedCount = 0;
        } catch (\PDOException $e) {
            $logger = $this->getDI()->getLogger();
            $logDir = $this->getDI()->getConfig()->path->errorLog 
                    . '/mysql'
                    . '/' . date('Ym') 
                    . '/' . date('d');
            $logName = 'mysql_query_exception_' . date('H');
            $logStr['code'] = $e->getCode();
            $logStr['message'] = $e->getMessage();
            $logStr['sqlStatement'] = $sqlStatement;
            $logStr = PHP_EOL .var_export($logStr, true);
            $logger->setDir($logDir)->setName($logName)->record($logStr)->save();
            
            if(
                    $e->getCode() != 'HY000' 
                    || !stristr($e->getMessage(), 'server has gone away')
                    || $this->reconnectTriedCount > self::RECONNECT_TRIED_MAX) {
                throw $e;
            }
            
            $this->reconnectTriedCount++;
            $this->close();
            $this->connect();
            $this->query($sqlStatement, $bindParams, $bindTypes);
        }
    }
}
