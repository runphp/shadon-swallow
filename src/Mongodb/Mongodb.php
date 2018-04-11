<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Mongodb;

use Swallow\Core\Base;
use Swallow\Exception\StatusCode;
use Swallow\Core\Conf;
use Swallow\Debug\Trace;
use Swallow\Exception\DbException;

/**
 * 模块 -> 模形基类
 *        提供与数据库的对接
 *
 * @author     chenjinggui<chenjinggui@eelly.net>
 * @since      2015年7月16日
 * @version    1.0
 */
abstract class Mongodb extends Base
{

    /**
     * 数据库对象
     * @var MongoDB
     */
    protected $db = null;

    /**
     * 集合名
     * @var MongoCollection
     */
    protected $c = '';

    /**
     * 集合名
     * @var string
     */
    protected $collectionName = '';

    /**
     * 原始集合名
     * @var string
     */
    protected $oCollectionName = '';

    /**
     * 字段
     * @var array
     */
    protected $fields = array();

    /**
     * 现在返回记录数
     * @var int
     */
    protected $limit = null;

    /**
     * 略过记录数
     * @var int
     */
    protected $skip = 0;

    /**
     * 排序
     * @var array
     */
    protected $sort = array();

    /**
     * 查询条件
     * @var array
     */
    protected $query = array();

    /**
     * 构造
     * 
     * @param   array   $config   数据库配置
     * @param   string  $db       数据库名称
     * @param   string  $collection 集合名称
     * @author  chenjinggui<chenjinggui@eelly.net>
     * @since   2015年7月20日
     * @version 1.0
     */
    function __construct($config, $db, $collection)
    {
        $conn = $this->retryMongo($config);
        $this->db = $conn->selectDB($db);
        $this->oCollectionName = $collection;
        $this->setCollection($collection);
        $this->init();
    }

    /**
     * 尝试多次链接，解决偶尔连不上mongo
     * 
     * @param  array   $config 
     * @param  int     $times  重试次数
     * @author chenjinggui<chenjinggui@eelly.net>
     * @since  2015年6月12日
     */
    private function retryMongo($config, $times = 3)
    {
        try {
            return new \MongoClient($config['server'], $config['options']);
        } catch (\MongoConnectionException $e) {
        }
        if ($times > 0) {
            return $this->retryMongo($config, -- $times);
        }
        Trace::dump('数据库连接出错，无法建立远程连接！');
    }

    /**
     * 初始化
     */
    protected function init()
    {
    }

    /**
     * 设置集合
     *
     * @param string $collectionName 集合名
     * @return \Swallow\Base\Mongo
     * @author zengzhihao<zengzhihao@eelly.net>
     * @since  2015年7月24日
     */
    public function setCollection($collectionName)
    {
        if (! is_string($collectionName)) {
            throw new DbException("mongodb的setCollection方法传入参数错误,正确字符串", StatusCode::INVALID_ARGUMENT);
        }
        $this->collectionName = Mongodb::lrtrim($collectionName);
        $this->c = $this->db->selectCollection($this->collectionName);
        return $this;
    }

    /**
     * 设置字段
     *
     * 示例： $model->fields('id, status, ctime')
     *      $model->fields(['id', 'status', 'ctime'])
     *
     * @param  mixed $fields 字段
     * @return self
     */
    public function field($fields)
    {
        $isPattern = false;
        if (is_string($fields)) {
            $fields = explode(',', $fields);
            $isPattern = true;
        }
        if (is_array($fields)) {
            $fieldsTmp = [];
            foreach ($fields as $field) {
                $fieldsTmp[] = $field;
            }
            $fields = $fieldsTmp;
            $isPattern = true;
        }
        if (! $isPattern) {
            throw new DbException("mongodb的field方法传入参数错误", StatusCode::INVALID_ARGUMENT);
        }
        foreach ($fields as $v) {
            $this->fields[Mongodb::lrtrim($v)] = 1;
        }
        if (isset($fields['id'])) {
            unset($fields['id']);
            $this->fields['_id'] = 1;
        } else {
            $this->fields['_id'] = 0;
        }
        return $this;
    }

    /**
     * 说明:设置限制
     * 
     * 示例： $model->limit(5, 100)
     * @param  int    $limit   返回的条数
     * @param  int    $skip    跳过n条记录
     * @return self
     */
    public function limit($limit, $skip = 0)
    {
        if (! is_numeric($limit)) {
            throw new DbException("mongodb的limit方法传入\$limit参数错误,正确为 1或是100等", StatusCode::INVALID_ARGUMENT);
        }
        if (! is_numeric($skip)) {
            throw new DbException("mongodb的limit方法传入\$skip参数错误,正确为 1或是100等", StatusCode::INVALID_ARGUMENT);
        }
        $this->limit = $limit;
        $this->skip = $skip;
        return $this;
    }

    /**
     * 说明:设置排序
     * 
     * 示例： id desc,status
     *
     * @param  string $order
     * @return self
     */
    public function order($order)
    {
        if (! is_string($order)) {
            throw new DbException("mongodb的ordor排序方法传入参数错误,正确为 'key'或是'key asc'或是'key1 asc,key2 asc'", StatusCode::INVALID_ARGUMENT);
        }
        $sortKeys = explode(',', $order);
        foreach ($sortKeys as $v) {
            $v = preg_replace("/[ ]{2,}/i", ' ', Mongodb::lrtrim($v));
            $key = explode(' ', $v, 2);
            $this->sort[$key[0]] = ((! isset($key[1])) || $key[1] == 'asc') ? 1 : - 1;
        }
        return $this;
    }

    /**
     * 说明:设置条件
     *
     * @param  mixed $where 条件可以为字串符可以为数组方式
     * @return self
     */
    public function where(array $where)
    {
        // 待扩展，解析
        $this->query = $where;
        return $this;
    }

    /**
     * 说明:选择
     *
     * @return array
     */
    public function select()
    {
        //$sql = $this->buildQuerySql();
        $cursor = $this->c->find($this->query, $this->fields)
            ->sort($this->sort)
            ->skip($this->skip)
            ->limit($this->limit);
        $return = array();
        foreach ($cursor as $doc) {
            $return[] = $doc;
        }
        $this->clearSet();
        return $return;
    }

    /**
     * 说明:选择一条数据
     *
     * @return array
     */
    public function find()
    {
        $return = $this->c->findOne($this->query, $this->fields);
        $this->clearSet();
        return $return;
    }

    /**
     * 说明:统计总数
     *
     * @return int
     */
    public function count()
    {
        $count = $this->c->count($this->query);
        $this->clearSet();
        return $count;
    }

    /**
     * 说明:删除
     *
     * @param  array   $options    原生可选参数
     *         int     w           默认1，设置写参数
     *         boole   justOne     默认true，设置是否删除多条
     *         boole   fsync       默认false,设置硬盘同步   
     *         boole   j           默认false,设置journal同步
     *         int     wTimeoutMS         默认1000
     * @return int 影响的行数
     */
    public function delete($options = array())
    {
        $return = $this->c->remove($this->query, $options);
        $this->clearSet();
        return $return;
    }

    /**
     * 说明:获取指定键的所有不同值
     * 
     * @param  string    $key 添加的数据
     * @return int
     */
    public function distinct($key)
    {
        $values = $this->c->distinct($key, $this->query);
        $this->clearSet();
        return $values;
    }

    /**
     * 说明:插入一条记录
     *
     * @param   array   $data   要添加的记录
     * @param   array   $options
     * @return array
     */
    public function add($data, $options = array())
    {
        return $this->c->insert($data, $options);
    }

    /**
     * 批量插入
     *
     * @param   array   $batch    要批量插入的数据，包含多个数组或者多个对象
     * @param   array   $options  对应mongo扩展集合对象批量插入的参数
     *          boole   continueOnError 默认false
     *          boole   fsync           默认false
     *          boole   j               默认false
     *          int     socketTimeoutMS 默认3000
     *          int/str w               WriteConcerns(替代不建议使用的safe)
     *          int     wTimeoutMS      默认1000(替代不建议使用的wtimeout)
     * @author chenjinggui<chenjinggui@eelly.net>
     * @since  2015年5月20日
     */
    public function addAll(array $batch, array $options = array())
    {
        return $this->c->batchInsert($batch, $options);
    }

    /**
     * 说明:更新记录
     * 
     * @param  array   $newObject      要更新的内容
     * @param  array   $options
     *         boole   upsert           默认false,设置无匹配时是否插入新记录
     *         boole   multiple         默认true，设置是否更新多条
     *         int     socketTimeoutMS  默认3000
     * @return array
     */
    public function save($newObject, $options = array())
    {
        $return = $this->c->update($this->query, $newObject, $options);
        $this->clearSet();
        return $return;
    }

    /**
     * 创建索引
     *
     * @param array $keys   形如：array('za' => 1, 'zb' => -1)
     * @param array $options
     *        boole     unique              默认false,设置是否为唯一索引
     *        boole     sparse              默认false,设置是否为希疏索引
     *        int       expireAfterSeconds  过期时间，会自动删除
     *        string    name
     *        boole     background          默认false,设置后台执行
     *        int       socketTimeoutMS     默认3000
     *        int       maxTimeMS
     *        boole     dropDups            默认false，设置是否去重
     *                  w
     *        int       wTimeoutMS          默认1000
     * @author chenjinggui<chenjinggui@eelly.net>
     * @since  2015年5月20日
     */
    public function createIndex(array $keys, array $options)
    {
        return $this->c->createIndex($keys, $options);
    }

    /**
     * 管道操作
     * http://php.net/manual/zh/mongocollection.aggregate.php
     * http://docs.mongodb.org/manual/meta/aggregation-quick-reference/
     * 
     * @param array $pipeline 管道
     * @author zengzhihao<zengzhihao@eelly.net>
     * @since  2015年7月25日
     */
    public function aggregate(array $pipeline)
    {
        return $this->c->aggregate($pipeline);
    }

    /**
     * 清空设置
     */
    private function clearSet()
    {
        $this->fields = $this->sort = $this->query = array();
        $this->limit = null;
        $this->skip = 0;
    }

    /**
     * 去除两边所有空格换行符
     * 
     * @param $str 字符串
     * @return str
     */
    static private function lrtrim($str)
    {
        $str = $str . '';
        return preg_replace("/^[\s]{0,}(.*)[\s]{0,}$/i", "$1", $str);
    }
}  
