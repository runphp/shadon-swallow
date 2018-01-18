<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Db;

use Swallow\Db\Db;
use Whoops\Exception\ErrorException;
use Swallow\Exception\DbException;
use Swallow\Core\Conf;

/**
 * Mysqli类
 *
 * @author    SpiritTeam
 * @since     2015年3月10日
 * @version   1.0
 */
class Mysqli implements Db
{

    /**
     * 配置
     * @var array
     */
    private $config = null;

    /**
     * 查询语句
     * @var string
     */
    private $querySql = null;

    /**
     * 查询库的类型（master | slave）
     * @var string
     */
    private $queryType = null;

    /**
     * 查询返回资源
     * @var \mysqli_result
     */
    private $queryObj = null;

    /**
     * mysql对象
     * @var array
     */
    private $links = array();

    /**
     * 连接key值
     * @var string
     */
    private $linkKey = '';

    /**
     * mysql对象
     * @var \mysqli
     */
    private $linkObj = null;

    /**
     * 最后插入ID
     * @var int
     */
    private $lastInsID = null;

    /**
     * 返回或者影响记录数
     * @var int
     */
    private $numRows = 0;

    /**
     * 返回字段数
     * @var int
     */
    private $numCols = 0;

    /**
     * 连接时间
     * @var array<int>
     */
    private $connTime = array();

    /**
     * 超时ping
     * @var int
     */
    private $timeoutPing = 30;

    /**
     * 错误
     * @var string
     */
    private $error = '';

    /**
     * 事务中
     * @var boolean
     */
    private $inTrans = false;

    /**
     * 初始化
     */
    public function __construct($conf)
    {
        $this->config['master'] = $conf['master'];
        $this->config['slave'] = $conf['slave'];
    }

    /**
     * 根据sql拿连接实例
     *
     * @param string $sql
     */
    private function setLink($sql = '')
    {
        $this->queryType = $qType = $this->getQueryType($sql);
        $conf = $this->config[$qType];
        if (isset($conf[0])) {
            shuffle($conf);
            $conf = current($conf);
        }
        $this->linkKey = $key = $qType . '_' . md5(serialize($conf));
        if (isset($this->links[$key]) && $this->check()) {
            $this->linkObj = $this->links[$key];
            return;
        }
        $this->linkObj = $this->links[$key] = $this->connect($conf);
    }

    /**
     * 查询前检查
     */
    private function check()
    {
        if (is_object($this->queryObj)) {
            $this->queryObj->free_result();
        }
        $this->queryObj = null;
        $instance = $this->links[$this->linkKey];
        if ($this->connTime[$this->linkKey] < time()) {
            //无法ping通 gone away 重连
            if (! $instance->ping() && $instance->errno == 2006) {
                //echo 'timeout ', var_dump($instance->errno), ' - ', var_dump($instance->error);
                return false;
            }
            $this->connTime[$this->linkKey] = time() + $this->timeoutPing;
        }
        return true;
    }

    /**
     * 根据语句返回是
     *
     * @param string $sql
     */
    private function getQueryType($sql)
    {
        $sql = strtolower($sql);
        //事务中所有查询都是主库
        if ($this->inTrans) {
            return 'master';
        }
        $queryType = 'slave';
        $operator = substr($sql, 0, stripos($sql, ' '));
        if (in_array($operator, array('insert', 'update', 'delete', 'replace'))) {
            $queryType = 'master';
        }
        if(!stripos($sql, ' join ') && (stripos($sql, 'ecm_pay_member') || stripos($sql, 'ecm_pay_query') || stripos($sql, 'ecm_pay_record'))){
            $queryType = 'master';
        }
        return $queryType;
    }

    /**
     * 连接数据库
     *
     * @param string $type
     * @param \mysqli
     */
    private function connect(array $config)
    {
        $link = new \mysqli($config['host'], $config['user'], $config['pass'], $config['dbname'], ! empty($config['port']) ? intval(
            $config['port']) : 3306);
        if (mysqli_connect_errno()) {
            trigger_error(mysqli_connect_error(), E_WARNING);
        }
        // 设置数据库编码
        $link->query("SET NAMES '" . (isset($config['charset']) ? $config['charset'] : 'utf8') . "'");
        $this->connTime[$this->linkKey] = time() + $this->timeoutPing;
        return $link;
    }

    /**
     * 获得所有的查询数据
     *
     * @param string $sql  sql语句
     * @return array
     */
    private function getAll()
    {
        //返回数据集
        $result = array();
        if ($this->numRows > 0) {
            //返回数据集
            for ($i = 0; $i < $this->numRows; $i ++) {
                $result[$i] = $this->queryObj->fetch_assoc();
            }
            $this->queryObj->data_seek(0);
        }
        return $result;
    }

    /**
     * 数据库错误信息
     * 并显示当前的SQL语句
     *
     * @return string
     */
    private function error()
    {
        $error = [
            'errno' => $this->linkObj->errno
        ];
        if ('' != $this->querySql) {
            $error['sql'] = $this->querySql;
        }
        return $this->error = json_encode($error);
    }

    /**
     * 更改行数
     *
     * @return int
     */
    public function affectedRows()
    {
        return $this->numRows;
    }

    /**
     * 插入的id
     *
     * @return int
     */
    public function insertId()
    {
        return $this->lastInsID;
    }

    /**
     * 错误字符串
     *
     * @return int
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * 执行查询 返回数据集
     *
     * @param  string $sql
     * @return mixed
     */
    public function query($sql)
    {
        $this->setLink($sql);
        if (! $this->linkObj) {
            return false;
        }
        $this->querySql = $sql;
        try {
            $startTime = time();
            $this->queryObj = $this->linkObj->query($sql);
            $used = time() - $startTime;
            $slowTime = Conf::get('Swallow/db/slow_sql_time') ?: 5;
            if($used >= $slowTime){
                \Swallow\Core\Log::warning('slow sql', ['sql' => $sql, 'used' => $used.'s']);
            }
        } catch (ErrorException $e) {
            throw new DbException(mysqli_error($this->linkObj) . ' ' . $this->error());
        }
        if (false === $this->queryObj) {
            throw new DbException(mysqli_error($this->linkObj) . ' ' . $this->error());
        } else {
            if (gettype($this->queryObj) == 'object') {
                $this->numRows = $this->queryObj->num_rows;
                $this->numCols = $this->queryObj->field_count;
            }
            return $this->getAll();
        }
    }

    /**
     * 执行语句
     *
     * @param  string $sql
     * @return int
     */
    public function execute($sql)
    {
        $this->setLink($sql);
        if (! $this->linkObj) {
            return false;
        }
        $this->querySql = $sql;
        try {
            $startTime = time();
            $result = $this->linkObj->query($sql);
            $used = time() - $startTime;
            $slowTime = Conf::get('Swallow/db/slow_sql_time') ?: 5;
            if($used >= $slowTime){
                \Swallow\Core\Log::warning('slow sql', ['sql' => $sql, 'used' => $used.'s']);
            }
        } catch (ErrorException $e) {
            throw new DbException(mysqli_error($this->linkObj) . ' ' . $this->error());
        }
        if (false === $result) {
            throw new DbException(mysqli_error($this->linkObj) . ' ' . $this->error());
        } else {
            $this->numRows = $this->linkObj->affected_rows;
            $this->lastInsID = $this->linkObj->insert_id;
            return $this->numRows;
        }
    }

    /**
     * 启动事务
     *
     * @return boolean
     */
    public function beginTransaction()
    {
        //数据rollback 支持
        $this->inTrans = true;
        $this->setLink();
        return $this->linkObj->autocommit(false);
    }

    /**
     * 用于非自动提交状态下面的查询提交
     *
     * @return boolean
     */
    public function commit()
    {
        if (! $this->inTrans) {
            return false;
        }
        $result = $this->linkObj->commit();
        if (! $result) {
            $this->error();
            return false;
        }
        return true;
    }

    /**
     * 事务回滚
     *
     * @return boolean
     */
    public function rollback()
    {
        if (! $this->inTrans) {
            return false;
        }
        $result = $this->linkObj->rollback();
        if (! $result) {
            $this->error();
            return false;
        }
        return true;
    }

    /**
     * 结束事务
     *
     * @return boolean
     */
    public function endTransaction()
    {
        if (! $this->inTrans) {
            return false;
        }
        $this->inTrans = false;
        return $this->linkObj->autocommit(true);
    }

    /**
     * 关闭
     */
    public function __destruct()
    {
        foreach ($this->links as $link) {
            $link->close();
        }
    }
}
