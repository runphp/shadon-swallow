<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Core;

use Swallow\Di\Injectable;

/**
 * 数据层
 *
 * @author     SpiritTeam
 * @since      2015年1月12日
 * @version    1.0
 */
class Db extends Injectable
{

    /**
     * db类
     * @var \Swallow\Driver\Db
     */
    private $db = null;

    /**
     * 表前缀
     * @var string
     */
    public static $prefix = '';

    private $sql = '';

    private $conf = [];

    /**
     * 获取db类
     *
     * @return self
     */
    /* public static function getInstance()
    {
        static $obj = null;
        if (isset($obj)) {
            return $obj;
        }
        $obj = new self();
        return $obj;
    } */

    /**
     * 构造
     *
     * @param  \Swallow\Driver\Db $db
     */
    public function __construct()
    {
        $di = $this->getDI();
        $this->setEventsManager($di->getEventsManager());
        $conf = $this->conf = $di->getConfig()->db->swallow->toArray();
        self::$prefix = $conf['prefix'];
        $this->db = $di->get('Swallow\Db\Mysqli', [$conf]);
    }

    /**
     * 查询数据
     *
     * @param  string $sql
     * @return mixed
     */
    public function query($sql)
    {
        $this->sql = $sql;
        $this->getEventsManager()->fire('db:beforeQuery', $this, $sql);
        $ret = $this->db->query($sql);
        $this->getEventsManager()->fire('db:afterQuery', $this, $ret);
        return $ret;
    }

    /*
     * 返回sql
     */
    public function getSQLStatement()
    {
        return $this->sql;
    }

    /*
     * 返回配置信息
     */
    public function getDescriptor()
    {
        return $this->conf;
    }

    /* public function getRealSQLStatement()
    {
        return $this->sql;
    } */

    /**
     * 执行语句
     *
     * @param  string $sql
     * @return mixed
     */
    public function execute($sql)
    {
        $this->getEventsManager()->fire('db:beforeQuery', $this, $sql);
        $ret = $this->db->execute($sql);
        $this->getEventsManager()->fire('db:afterQuery', $this, $ret);
        return $ret;
    }

    /**
     * 更改行数
     *
     * @return int
     */
    public function affectedRows()
    {
        return $this->db->affectedRows();
    }

    /**
     * 插入的id
     *
     * @return int
     */
    public function insertId()
    {
        return $this->db->insertId();
    }

    /**
     * 错误字符串
     *
     * @return int
     */
    public function getError()
    {
        return $this->db->getError();
    }

    /**
     * 提交
     *
     * @return boolean
     */
    public function commit()
    {
        return $this->db->commit();
    }

    /**
     * 回滚
     *
     * @return boolean
     */
    public function rollback()
    {
        return $this->db->rollback();
    }

    /**
     * 开启事务
     *
     * @return boolean
     */
    public function beginTransaction()
    {
        return $this->db->beginTransaction();
    }

    /**
     * 结事事务
     *
     * @return boolean
     */
    public function endTransaction()
    {
        return $this->db->endTransaction();
    }
}
