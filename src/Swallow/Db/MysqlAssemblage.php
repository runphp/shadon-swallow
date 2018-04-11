<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */

namespace Swallow\Db;

/**
 * Mysql池。
 *
 * @author     SpiritTeam
 * @since      2015年8月13日
 * @version    1.0
 */
class MysqlAssemblage implements DbAssemblageInterfase,
                                    \Phalcon\Di\InjectionAwareInterface
{
    /**
     * 依赖注入
     *
     * @var \Swallow\Di\FactoryDefault
     */
    protected $_dependencyInjector;

    /**
     * 配置文件
     *
     * @var \Swallow\Config 
     */
    private $options;

    /**
     * 是否主从延迟
     *
     * @var bool
     */
    private $isSlaveDelayed = false;

    /**
     * 是否已经检查主从延迟
     *
     * @var bool
     */
    private $hasCheckSlaveDelayed = false;

    /**
     * 只使用主库
     *
     * @var bool
     */
    private $isMasterForced = false;

    /**
     * 一个主库
     *
     * @var \Swallow\Db\Mysql
     */
    private $master;

    /**
     * 多个从库
     *
     * @var array 每个元素都是\Swallow\Db\Mysql
     */
    private $slave;

    /**
     *
     * @author    SpiritTeam
     * @since     2015年8月12日
     * @version   1.0
     */
    public function __construct($options = null)
    {
        $this->options = empty ($options) ? $this->getDI()->config->database : $options;
        if (!isset($this->options->master) || !isset($this->options->slave)) {
            throw new \Swallow\Exception\DbException('You have to set the master and slave databases.');
        }
    }

    /**
     *
     * @param \Phalcon\DiInterface $dependencyInjector
     *
     * @author    SpiritTeam
     * @since     2015年8月12日
     * @version   1.0
     */
    public function setDI(\Phalcon\DiInterface $dependencyInjector)
    {
        $this->_dependencyInjector = $dependencyInjector;
    }

    /**
     *
     * @return \Phalcon\DiInterface $dependencyInjector
     *
     * @author    SpiritTeam
     * @since     2015年8月12日
     * @version   1.0
     */
    public function getDI()
    {
        return $this->_dependencyInjector;
    }

    /**
     * 返回读数据库连接
     *
     * @param array $intermediate
     * @param array $bindParams
     * @param array $bindTypes
     *
     * @author    SpiritTeam
     * @since     2015年8月12日
     * @version   1.0
     */
    public function selectReadConnection($intermediate, $bindParams, $bindTypes)
    {
        $db = null;
        if ($this->isMasterForced || $this->isSlaveDelayed()) {
            $db = $this->getMaster();
        } else {
            $db = $this->getSlave(true);
        }

        return $db;
    }

    /**
     * 返回写数据库连接
     *
     * @param array $intermediate
     * @param array $bindParams
     * @param array $bindTypes
     *
     * @author    SpiritTeam
     * @since     2015年8月12日
     * @version   1.0
     */
    public function selectWriteConnection($intermediate, $bindParams, $bindTypes)
    {
        return $this->getMaster();
    }

    /**
     * 强制使用主库
     *
     * @author    SpiritTeam
     * @since     2015年8月12日
     * @version   1.0
     */
    public function setMasterForced()
    {
        $this->isMasterForced = true;
    }

    /**
     * 清除所有数据库连接集合。
     * 主要应该场景是自动任务里面的死循环队列处理，防止主从延迟的情况下
     * 一直都连着主库。
     *
     * @author    SpiritTeam
     * @since     2015年8月12日
     * @version   1.0
     *
     */
    public function clearConnections()
    {
        $this->getMaster()->close();
        $this->master = null;

        $slave = $this->getSlave();
        foreach ($slave as $slaveDb) {
            $slaveDb->close();
        }
        $this->slave = null;

        $this->hasCheckSlaveDelayed = false;
        $this->isSlaveDelayed = false;
    }

    /**
     * 获取主库对象
     *
     * @return \Swallow\Db\Mysql
     *
     * @author    SpiritTeam
     * @since     2015年8月12日
     * @version   1.0
     */
    public function getMaster()
    {
        if (empty($this->master)) {
            $this->master = $this->initConnection($this->getDI()->config->database->master);
        }

        return $this->master;
    }

    /**
     * 获取从库对象数组
     *
     * @param bool $isGetOne 是否只取一个
     * @return mix 
     *  object: \Swallow\Db\Mysql
     *  array: 每个元素都是 \Swallow\Db\Mysql
     *
     * @author    SpiritTeam
     * @since     2015年8月12日
     * @version   1.0
     */
    public function getSlave($isGetOne = false)
    {
        if (empty($this->slave)) {
            foreach ($this->getDI()->config->database->slave as $slave) {
                $this->slave[] = $this->initConnection($slave);
            }
        }

        if ($isGetOne) {
            //随机取一个从库连接
            shuffle($this->slave);
            return $this->slave[0];
        } else {
            return $this->slave;
        }
    }

    /**
     * 初始化数据库连接
     *
     * @param object $config
     *
     * @author    SpiritTeam
     * @since     2015年8月12日
     * @version   1.0
     */
    protected function initConnection($config)
    {
        $dbConnection = new \Swallow\Db\Mysql([
            "host"     => $config->host,
            "username" => $config->username,
            "password" => $config->password,
            "dbname"   => $config->dbname,
            "charset"  => $config->charset,
        ]);

        //准备一些记录
        $params = [];
        $params[] = 'datetime_' . date('Ymd_His');
        $params[] = 'host_' . $config->hosts;
        if (isset($this->getDI()->dispatcher)) {
            $dispatcher = $this->getDI()->dispatcher;
            $params[] = 'module_' . $dispatcher->getModuleName();
            $params[] = 'controller_' . $dispatcher->getControllerName();
            $params[] = 'action_' . $dispatcher->getActionName();
        }
        if (isset($this->getDI()->user)) {
            $params[] = 'userid_' . (empty($this->getDI()->user->user_id) ? -1 : $this->getDI()->user->user_id);
        }
        $sql = 'SELECT 1 /** ' . implode('/' , $params) . ' **/';
        $dbConnection->execute($sql);

        return $dbConnection;
    }

    /**
     * 检查主从延迟
     *
     * @author    SpiritTeam
     * @since     2015年8月12日
     * @version   1.0
     */
    private function isSlaveDelayed()
    {
        if (!$this->hasCheckSlaveDelayed) {
            $master = $this->getMaster();
            $slave = $this->getSlave();
            $sql = 'SELECT sync_value FROM ecm_db_sync LIMIT 1';

            $isMatched = false;
            $masterValue = $master->query($sql)->fetch();
            foreach ($slave as $slaveConnection) {
                $tmpValue = $slaveConnection->query($sql)->fetch();
                if ($masterValue == $tmpValue) {
                    $isMatched = true;
                    break;
                }
            }
            !$isMatched && $this->isSlaveDelayed = true;

            $this->hasCheckSlaveDelayed = true;
        }

        return $this->isSlaveDelayed;
    }
}
