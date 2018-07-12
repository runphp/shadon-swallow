<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Core;

use Swallow;

/**
 * 数据层
 * 
 * @author     SpiritTeam
 * @since      2015年1月12日
 * @version    1.0
 */
class Db
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

    /**
     * 获取db类 
     * 
     * @param string $model
     * @return self
     */
    public static function getInstance($model = '')
    {
        $model = trim($model);
        $model = ! empty($model) ? $model : 'Swallow';
        $conf = Conf::get(ucfirst($model) . '/db');
        if(empty($conf)){
            $model = 'Swallow';
            $conf = Conf::get('Swallow/db');
        }
        static $obj = [];
        if (isset($obj[$model])) {
            self::$prefix = $conf['prefix'];
            return $obj[$model];
        }
        
        // 如果配置有从库，就从中随机选择一个从库
        if (isset($conf['slave'])) {
            if (count($conf['slave']) > 1) {
                $slaveIndex = array_rand($conf['slave']);
            } else {
                $slaveIndex = 0;
            }
            $conf['slave'] = $conf['slave'][$slaveIndex];
        }
        
        $type = ucfirst($conf['type']);
        self::$prefix = $conf['prefix'];
        $class = '\\Swallow\\Db\\' . $type;
        if (! class_exists($class)) {
            return $obj = false;
        }
        $obj[$model] = new self(new $class(array('master' => $conf['master'], 'slave' => $conf['slave'])));
        return $obj[$model];
    }

    /**
     * 构造
     * 
     * @param  \Swallow\Driver\Db $db
     */
    public function __construct(\Swallow\Db\Db $db)
    {
        $this->db = $db;
    }

    /**
     * 查询数据
     * 
     * @param  string $sql
     * @return mixed
     */
    public function query($sql)
    {
        return $this->db->query($sql);
    }

    /**
     * 执行语句
     *
     * @param  string $sql
     * @return mixed
     */
    public function execute($sql)
    {
        return $this->db->execute($sql);
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
