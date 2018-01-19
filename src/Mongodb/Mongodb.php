<?php

declare(strict_types=1);

/*
 * This file is part of eelly package.
 *
 * (c) eelly.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swallow\Mongodb;

use Swallow\Core\Base;
use Swallow\Core\Log;
use Swallow\Exception\DbException;
use Swallow\Exception\StatusCode;
use Swallow\Mongodb\Exception\MongoDuplicateKeyException;

/**
 * 模块 -> 模形基类
 *        提供与数据库的对接.
 *
 * @author     chenjinggui<chenjinggui@eelly.net>
 *
 * @since      2015年7月16日
 *
 * @version    1.0
 */
abstract class Mongodb extends Base
{
    /**
     * 数据库对象
     *
     * @var \MongoDB\Database
     */
    protected $db = null;

    /**
     * 集合名.
     *
     * @var \MongoDB\Collection
     */
    protected $c = '';

    /**
     * 集合名.
     *
     * @var string
     */
    protected $collectionName = '';

    /**
     * 原始集合名.
     *
     * @var string
     */
    protected $oCollectionName = '';

    /**
     * 字段.
     *
     * @var array
     */
    protected $fields = [];

    /**
     * 现在返回记录数.
     *
     * @var int
     */
    protected $limit = null;

    /**
     * 略过记录数.
     *
     * @var int
     */
    protected $skip = 0;

    /**
     * 排序.
     *
     * @var array
     */
    protected $sort = [];

    /**
     * 查询条件.
     *
     * @var array
     */
    protected $query = [];

    /**
     * 当前插入记录ID.
     *
     * @var string
     */
    protected $insertId = 0;
    /**
     * @var \MongoDB\Client
     */
    private $connection;

    /**
     * @var \Swallow\Mvc\Collection\Manager
     */
    private $collectionManager;

    /**
     * 构造.
     *
     * @param array  $config     数据库配置
     * @param string $db         数据库名称
     * @param string $collection 集合名称
     *
     * @author  chenjinggui<chenjinggui@eelly.net>
     *
     * @since   2015年7月20日
     *
     * @version 1.0
     */
    public function __construct($config, $db, $collection)
    {
        $di = \Phalcon\Di::getDefault();
        $this->collectionManager = $di->get('collectionManager');
        $this->connection = $di->has('mongo_'.$db) ? $di->get('mongo_'.$db) : $di->get('mongo_default');
        $this->db = $this->connection->selectDatabase($db);
        $this->oCollectionName = $collection;
        $this->setCollection($collection);
        $this->init();
    }

    /**
     * 设置集合.
     *
     * @param string $collectionName 集合名
     *
     * @return \Swallow\Base\Mongo
     *
     * @author zengzhihao<zengzhihao@eelly.net>
     *
     * @since  2015年7月24日
     */
    public function setCollection($collectionName)
    {
        if (!is_string($collectionName)) {
            throw new DbException('mongodb的setCollection方法传入参数错误,正确字符串', StatusCode::INVALID_ARGUMENT);
        }
        $this->collectionName = self::lrtrim($collectionName);
        $this->c = $this->db->selectCollection($this->collectionName);

        return $this;
    }

    /**
     * 设置字段.
     *
     * 示例： $model->fields('id, status, ctime')
     *      $model->fields(['id', 'status', 'ctime'])
     *
     * @param mixed $fields 字段
     *
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
        if (!$isPattern) {
            throw new DbException('mongodb的field方法传入参数错误', StatusCode::INVALID_ARGUMENT);
        }
        foreach ($fields as $v) {
            $this->fields[self::lrtrim($v)] = 1;
        }
        if (isset($this->fields['id'])) {
            unset($this->fields['id']);
            $this->fields['_id'] = 1;
        } else {
            $this->fields['_id'] = 0;
        }

        return $this;
    }

    /**
     * 说明:设置限制.
     *
     * 示例： $model->limit(5, 100)
     *
     * @param int $limit 返回的条数
     * @param int $skip  跳过n条记录
     *
     * @return self
     */
    public function limit($limit, $skip = 0)
    {
        if (!is_numeric($limit)) {
            throw new DbException('mongodb的limit方法传入$limit参数错误,正确为 1或是100等', StatusCode::INVALID_ARGUMENT);
        }
        if (!is_numeric($skip)) {
            throw new DbException('mongodb的limit方法传入$skip参数错误,正确为 1或是100等', StatusCode::INVALID_ARGUMENT);
        }
        $this->limit = $limit;
        $this->skip = $skip;

        return $this;
    }

    /**
     * 说明:设置排序.
     *
     * 示例： id desc,status
     *
     * @param string $order
     *
     * @return self
     */
    public function order($order)
    {
        if (!is_string($order)) {
            throw new DbException("mongodb的ordor排序方法传入参数错误,正确为 'key'或是'key asc'或是'key1 asc,key2 asc'", StatusCode::INVALID_ARGUMENT);
        }
        $sortKeys = explode(',', $order);
        foreach ($sortKeys as $v) {
            $v = preg_replace('/[ ]{2,}/i', ' ', self::lrtrim($v));
            $key = explode(' ', $v, 2);
            $this->sort[$key[0]] = ((!isset($key[1])) || 'asc' == $key[1]) ? 1 : -1;
        }

        return $this;
    }

    /**
     * 说明:设置条件.
     *
     * @param mixed $where 条件可以为字串符可以为数组方式
     *
     * @return self
     */
    public function where(array $where)
    {
        // 待扩展，解析
        $this->query = $where;

        return $this;
    }

    /**
     * 说明:选择.
     *
     * @return array
     */
    public function select()
    {
        $options = [
            'projection' => $this->fields,
            'limit'      => $this->limit,
            'sort'       => $this->sort,
            'skip'       => $this->skip,
        ];
        $startTime = time();
        $cursor = $this->c->find($this->query, $options);
        $used = time() - $startTime;
        $slowTime = $this->collectionManager->getSlowLogTime($this->db->getDatabaseName());
        if ($slowTime <= $used) {
            Log::warning('slow mongodb query', ['collection' => $this->c->getCollectionName(), 'query' => ['$conditions' => $this->query, '$options' => $options], 'used' => $used.'s']);
        }
        $this->clearSet();
        $cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);
        $return = $cursor->toArray();

        return $return;
    }

    /**
     * 说明:选择一条
     *
     * @return array
     */
    public function selectOne()
    {
        $this->limit = 1;
        $return = $this->select();
        if (empty($return)) {
            return null;
        } else {
            return current($return);
        }
    }

    /**
     * 说明:选择一条数据.
     *
     * @return array
     */
    public function find()
    {
        return $this->selectOne();
    }

    /**
     * 说明:统计总数.
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
     * 说明:删除.
     *
     * @param array $options 原生可选参数
     *                       int     w           默认1，设置写参数
     *                       boole   justOne     默认true，设置是否删除多条
     *                       boole   fsync       默认false,设置硬盘同步
     *                       boole   j           默认false,设置journal同步
     *                       int     wTimeoutMS         默认1000
     *
     * @return int 影响的行数
     */
    public function delete($options = [])
    {
        $return = $this->c->deleteMany($this->query, $options);
        $this->clearSet();

        return $return->getDeletedCount();
    }

    /**
     * 说明:获取指定键的所有不同值
     *
     * @param string $key 添加的数据
     *
     * @return int
     */
    public function distinct($key)
    {
        $values = $this->c->distinct($key, $this->query);
        $this->clearSet();

        return $values;
    }

    /**
     * 说明:插入一条记录.
     *
     * @param array $data    要添加的记录
     * @param array $options
     *
     * @return array
     */
    public function add($data, $options = [])
    {
        try {
            $return = $this->c->insertOne($data, $options);
        } catch (\MongoDB\Driver\Exception\BulkWriteException $e) {
            if (11000 == $e->getWriteResult()->getWriteErrors()[0]->getCode()) {
                throw new MongoDuplicateKeyException($e->getMessage(), 11000, $e);
            } else {
                throw $e;
            }
        }
        $this->insertId = $return->getInsertedId();

        return ['ok' => 1];
    }

    /**
     * 批量插入.
     *
     * @param array $batch   要批量插入的数据，包含多个数组或者多个对象
     * @param array $options 对应mongo扩展集合对象批量插入的参数
     *                       boole   continueOnError 默认false
     *                       boole   fsync           默认false
     *                       boole   j               默认false
     *                       int     socketTimeoutMS 默认3000
     *                       int/str w               WriteConcerns(替代不建议使用的safe)
     *                       int     wTimeoutMS      默认1000(替代不建议使用的wtimeout)
     *
     * @author chenjinggui<chenjinggui@eelly.net>
     *
     * @since  2015年5月20日
     */
    public function addAll(array $batch, array $options = [])
    {
        $return = $this->c->insertMany(array_values($batch), $options);

        return ['ok' => 1];
    }

    /**
     * 说明:更新记录.
     *
     * @param array $newObject 要更新的内容
     * @param array $options
     *                         boole   upsert           默认false,设置无匹配时是否插入新记录
     *                         boole   multiple         默认true，设置是否更新多条
     *                         int     socketTimeoutMS  默认3000
     *
     * @return array
     */
    public function save($newObject, $options = [])
    {
        $result = $this->c->updateMany($this->query, $newObject, $options);
        $this->clearSet();
        $resultArray = [
            'ok'              => 1.0,
            'nModified'       => $result->getModifiedCount(),
            'n'               => $result->getUpsertedCount() + $result->getModifiedCount(),
            'err'             => null,
            'errmsg'          => null,
            'updatedExisting' => 0 == $result->getUpsertedCount(),
        ];

        return $resultArray;
    }

    /**
     * 创建索引.
     *
     * @param array $keys    形如：array('za' => 1, 'zb' => -1)
     * @param array $options
     *                       boole     unique              默认false,设置是否为唯一索引
     *                       boole     sparse              默认false,设置是否为希疏索引
     *                       int       expireAfterSeconds  过期时间，会自动删除
     *                       string    name
     *                       boole     background          默认false,设置后台执行
     *                       int       socketTimeoutMS     默认3000
     *                       int       maxTimeMS
     *                       boole     dropDups            默认false，设置是否去重
     *                       w
     *                       int       wTimeoutMS          默认1000
     *
     * @author chenjinggui<chenjinggui@eelly.net>
     *
     * @since  2015年5月20日
     */
    public function createIndex(array $keys, array $options)
    {
        return $this->c->createIndex($keys, $options);
    }

    /**
     * 管道操作
     * http://php.net/manual/zh/mongocollection.aggregate.php
     * http://docs.mongodb.org/manual/meta/aggregation-quick-reference/.
     *
     * @param array $pipeline 管道
     *
     * @author zengzhihao<zengzhihao@eelly.net>
     *
     * @since  2015年7月25日
     */
    public function aggregate(array $pipeline)
    {
        $result = $this->c->aggregate($pipeline, [
            'typeMap' => [
                'root'     => 'array',
                'document' => 'array',
                'array'    => 'array',
            ],
        ]);

        return [
            'ok'       => 1.0,
            'result'   => $result->toArray(),
            'waitedMS' => 0,
        ];
    }

    /**
     * 说明:查找并更新记录.
     *
     * @param array $newObject 要更新的内容
     * @param array $options
     *
     * @return unknown
     *
     * @author zengzhihao<zengzhihao@eelly.net>
     *
     * @since  2016年4月21日
     */
    public function findAndModify(array $newObject, array $options = [])
    {
        $return = $this->c->findOneAndUpdate($this->query, $newObject, $options);
        $this->clearSet();

        return $return;
    }

    /**
     * 实现mongodb自增id.
     *
     * @param string $field
     *
     * @return number|int
     *
     * @author  heyanwen<heyanwen@eelly.net>
     *
     * @since   2016年10月12日
     */
    public function getNextId($field = null)
    {
        $collection = $this->db->selectCollection('counter'); // 数据库中counter做自增字段集合
        $field = $this->collectionName.'_seq'; // 自增字段
        $res = $collection->findOneAndUpdate(['_id' => $field], ['$inc' => ['seq' => 1]], [], ['new' => true]); // 自增
        $seqNum = $res ? (int) $res['seq'] : (int) $collection->insertOne(['_id' => $field, 'seq' => 1]); // 字段不存在则添加

        $this->clearSet();

        return $seqNum;
    }

    /**
     * 初始化.
     */
    protected function init(): void
    {
    }

    /**
     * 清空设置.
     */
    private function clearSet(): void
    {
        $this->fields = $this->sort = $this->query = [];
        $this->limit = null;
        $this->skip = 0;
    }

    /**
     * 去除两边所有空格换行符.
     *
     * @param $str 字符串
     *
     * @return string
     */
    private static function lrtrim($str)
    {
        $str = $str.'';

        return preg_replace("/^[\s]{0,}(.*)[\s]{0,}$/i", '$1', $str);
    }
}
