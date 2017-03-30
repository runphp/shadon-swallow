<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Mvc;

use \Phalcon\Mvc\CollectionInterface;
/**
 * mongo模块基类
 *
 * @author     SpiritTeam
 * @since      2015年8月13日
 * @version    1.0
 */
class Collection extends \Phalcon\Mvc\Collection
{

    /**
     * @return self
     *
     * @param $isNewInstance
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年10月13日
     */
    public static function getInstance($isNewInstance = false)
    {
        $defaultDi = \Phalcon\Di::getDefault();
        $className = static::class; //get_called_class()
        $modelObj = ($isNewInstance === false) ? $defaultDi->getShared($className) : $defaultDi->get($className);
        return $modelObj;
    }

    /**
     * 初始化
     *
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年10月13日
     */
    public function initialize()
    {
        $defaultDi = $this->getDI();
        $dbMongo = 'MongoDB';

        $className = static::class;
        $module = strtolower(explode('\\', $className)[0]);
        $file = ROOT_PATH . '/application/' . $module . '/config/' . APPLICATION_ENV . '/mongo.php';
        $dbMongo .= $module;
        if (is_file($file)) {
            $db = include $file;
            if (! empty($db) && isset($db['conn'])) {
                $self = $this;
                $defaultDi[$dbMongo] = function () use($db, $defaultDi, $self) {
                    $mongoClient =  new \MongoDB\Client($db['conn']['server'], $db['conn']['options']);
                    /* @var \MongoDB\Client $mongoClient */
                    $database = $mongoClient->selectDatabase($db['conn']['db']);
                    return $database;
                };
            }
        }
        $this->setConnectionService($dbMongo);
    }

    /**
     * 尝试多次链接，解决偶尔连不上mongo
     *
     * @param  array   $config
     * @param  int     $times  重试次数
     * @author chenjinggui<chenjinggui@eelly.net>
     * @since  2015年6月12日
     */
    protected function retryMongo($config, $times = 3)
    {
        try {
            return new \MongoClient($config['server'], $config['options']);
        } catch (\MongoConnectionException $e) {
        }
        if ($times > 0) {
            return $this->retryMongo($config, -- $times);
        }

        throw new \ErrorException('mongo service can not connect!'.json_encode($config));
    }


    /**
     * mongo库选择
     *
     * @param string $db 库名
     * @author hehui<hehui@eelly.net>
     * @since 2016年10月4日
     */
    protected function selectDb($db)
    {
        $di = $this->getDI();
        if (! $di->has('mongo_' . $db)) {
            $di->set('mongo_' . $db, function () use ($di, $db) {
                $mongoClient = $di->get('mongo');
                /* @var \MongoDB\Client $mongoClient */
                $database = $mongoClient->selectDatabase($db);
                return $database;
            }, true);
        }
        return $this->setConnectionService('mongo_' . $db);
    }

    /**
     * 转换数组到对象
     *
     *
     * @param array $data
     * @return \Swallow\Mvc\Collection
     * @author hehui<hehui@eelly.net>
     * @since  2016年10月5日
     */
    public static function hydrator(array $data = [])
    {
        $object = new static();
        $reflect = new \ReflectionObject($object);
        foreach ($data as $name => $value) {
            if ($reflect->hasProperty($name)) {
                $prop = $reflect->getProperty($name);
                $prop->setAccessible(true);
                $prop->setValue($object, $value);
            }
        }
        return $object;
    }

    /**
     * 获取某字段最大值
     *
     * @param string $field
     * @return number|mixed
     * @author hehui<hehui@eelly.net>
     * @since  2016年10月5日
     */
    public static function getMaxValue($field)
    {
        $clazz = get_called_class();
        $result = $clazz::aggregate([
            "\$group" => [
                "_id" => "",
                'max_value' => [
                    "\$max" => "\$$field"
                ]
            ]
        ]);
        $it = new \IteratorIterator($result);
        $it->rewind();
        $maxValue = $it->current()['max_value'];
        return $maxValue;
    }

    /**
     * (non-PHPdoc)
     * @see \Phalcon\Mvc\Collection::save()
     */
    public function save()
    {
        try {
            return $this->saveForMongoDb();
        } catch (\MongoDB\Driver\Exception\BulkWriteException $e) {
            if (11000 == $e->getWriteResult()->getWriteErrors()[0]->getCode()) {
                throw new MongoDuplicateKeyException($e->getMessage(), 11000, $e);
            } else {
                throw $e;
            }
        }
    }

    /**
     * (non-PHPdoc)
     *
     * @see \Phalcon\Mvc\Collection::findById()
     */
    public static function findById($id)
    {
        return self::findByIdForMongodb($id);
    }

    /**
     *
     *
     *
     * @param array $parameters
     * @author hehui<hehui@eelly.net>
     * @since  2017年3月14日
     */
    public static function aggregate(array $parameters = null)
    {
        $result = parent::aggregate($parameters);
        $return = ['result' => $result->toArray()];
        return $return;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \Phalcon\Mvc\Collection::delete()
     */
    public function delete()
    {
        return $this->deleteForMongodb();
    }

    /**
     * (non-PHPdoc)
     *
     * @see \Phalcon\Mvc\Collection::setId()
     */
    public function setId($id)
    {
        if ('object' != gettype($id)) {
            /**
             * Check if the model use implicit ids
             */
            if ($this->_modelsManager->isUsingImplicitObjectIds($this)) {
                $mongoId = new \MongoDB\BSON\ObjectID($id);
            } else {
                $mongoId = $id;
            }
        } else {
            $mongoId = $id;
        }
        $this->_id = $mongoId;
    }

    /**
     * 获取collection
     *
     * @return \MongoDB\Collection
     * @author hehui<hehui@eelly.net>
     * @since  2017年3月23日
     */
    public static function getCollection()
    {
        $className = get_called_class();
        $collection = new $className();
        return $collection->prepareCU();
    }

    /**
     * Returns a collection resultset
     *
     * @param array params
     * @param \Phalcon\Mvc\Collection collection
     * @param \MongoDb connection
     * @param boolean unique
     * @return array
     */
    protected static function _getResultset($params, CollectionInterface $collection, $connection, $unique)
    {
        return self::_getResultsetForMongoDb($params, $collection, $connection, $unique);
    }

    /**
     * (non-PHPdoc)
     * @see \Phalcon\Mvc\Collection::_exists()
     */
    protected function _exists($collection)
    {
        if (!isset($this->_id)) {
            return false;
        } else {
            $id = $this->_id;
        }
        if ('object' == gettype($this->_id)) {
            $mongoId = $id;
        } else {
            /**
             * Check if the model use implicit ids
             */
            if ($this->_modelsManager->isUsingImplicitObjectIds($this)) {
                $mongoId = new \MongoDB\BSON\ObjectID($id);
            } else {
                $mongoId = $id;
            }
        }
        /**
         * Perform the count using the function provided by the driver
         */
        return $this->count([["_id" => $mongoId]]) > 0;
    }

    /**
     * (non-PHPdoc)
     * @see \Phalcon\Mvc\Collection::prepareCU()
     */
    protected function prepareCU()
    {
        $dependencyInjector = $this->_dependencyInjector;
        if ('object' != gettype($dependencyInjector)) {
            throw new Exception("A dependency injector container is required to obtain the services related to the ODM");
        }
        $source = $this->getSource();
        if (empty($source)) {
            throw new Exception("Method getSource() returns empty string");
        }
        $connection = $this->getConnection();
        /**
         * Choose a collection according to the collection name
         */
        $collection = $connection->selectCollection($source);
        return $collection;
    }

    /**
     * Returns a collection resultset for mongodb
     *
     * @param array params
     * @param \Phalcon\Mvc\Collection collection
     * @param \MongoDb connection
     * @param boolean unique
     * @return array
     */
    private static function _getResultsetForMongoDb($params, CollectionInterface $collection, $connection, $unique)
    {
        /**
         * Check if "class" clause was defined
         */
        if (isset($params['class'])) {
            $className =$params['class'];
            $base = new $className();
            if (!($base instanceof CollectionInterface || $base instanceof Document)) {
                throw new Exception("Object of class '" . $className . "' must be an implementation of Phalcon\\Mvc\\CollectionInterface or an instance of Phalcon\\Mvc\\Collection\\Document");
            }
        } else {
            $base = $collection;
        }
        $source = $collection->getSource();
        if (empty($source)) {
            throw new Exception("Method getSource() returns empty string");
        }
        $mongoCollection = $connection->selectCollection($source);
        if ('object' != gettype($mongoCollection)) {
            throw new Exception("Couldn't select mongo collection");
        }
        /**
         * Convert the string to an array
         */
        if (isset($params['conditions'])) {
            $conditions = $params['conditions'];
        } elseif (isset($params[0])) {
            $conditions = $params[0];
        } else {
            $conditions = [];
        }

        if ('array' != gettype($conditions)) {
            throw new Exception("Find parameters must be an array");
        }

        $options = [];

        /**
         * Perform the find
         */
        if (isset($params["fields"])) {
            $options['projection'] = $params["fields"];
        }

        /**
         * Check if a "limit" clause was defined
         */
        if (isset($params["limit"])) {
            $options['limit'] = $params["limit"];
        }

        /**
         * Check if a "sort" clause was defined
         */
        if (isset($params["sort"])) {
            $options['sort'] = $params["sort"];
        }

        /**
         * Check if a "skip" clause was defined
         */
        if (isset($params["skip"])) {
            $options['skip'] = $params["skip"];
        }
        $documentsCursor = $mongoCollection->find($conditions, $options);
        $it = new \IteratorIterator($documentsCursor);
        if (true === $unique) {
            $it->rewind();

            /**
             * Requesting a single result
             */
            $document = $it->current();
            if ($document instanceof \MongoDB\Model\BSONDocument) {

                /**
                 * Assign the values to the base object
                 */
                return static::cloneResult($base, $document->getArrayCopy());
            } else {
                return false;
            }
        }

        /**
         * Requesting a complete resultset
         */
        $collections = [];
        $it->rewind();
        while($document = $it->current()) {
            $collections[] = static::cloneResult($base, $document->getArrayCopy());
            $it->next();
        }

        return $collections;
    }

    /**
     * Creates/Updates a collection based on the values in the attributes
     *
     * @author hehui<hehui@eelly.net>
     * @since 2017年3月13日
     */
    private function saveForMongoDb()
    {
        /*@var \MongoDB\Collection $collection */
        $collection = $this->prepareCU();

        /**
         * Check the dirty state of the current operation to update the current operation
         */
        $exists = $this->_exists($collection);

        /**
         * The messages added to the validator are reset here
         */
        $this->_errorMessages = [];

        /**
         * Execute the preSave hook
         */
        if ($this->_preSave($this->_dependencyInjector, self::$_disableEvents, $exists) === false) {
            return false;
        }

        $data = $this->toArray();
        $success = true;

        if ($exists) {
            $collection->updateOne([ '_id' => $this->_id ], [ "\$set" => $data ])->getModifiedCount();
        } else {
            $this->_id = $collection->insertOne($data)->getInsertedId();
        }

        /**
         * Call the postSave hooks
         */
        return $this->_postSave(self::$_disableEvents, $success, $exists);
    }

    /**
     * Find a document by its id (_id)
     *
     *
     * @param unknown $id
     * @author hehui<hehui@eelly.net>
     * @since  2017年3月13日
     */
    private static function findByIdForMongodb($id)
    {
        if ('object' != gettype($id)) {
            if (! preg_match("/^[a-f\d]{24}$/i", $id)) {
                return null;
            }
            $className = get_called_class();
            $collection = new $className();
            /**
             * Check if the model use implicit ids
             */
            if ($collection->getCollectionManager()->isUsingImplicitObjectIds($collection)) {
                $mongoId = new \MongoDB\BSON\ObjectID($id);
            } else {
                $mongoId = $id;
            }
        } else {
            $mongoId = $id;
        }
        return static::findFirst([ [ "_id" => $mongoId ] ]);
    }

    /**
     *
     *
     *
     * @author hehui<hehui@eelly.net>
     * @since  2017年3月14日
     */
    private function deleteForMongodb()
    {
        if (isset($this->_id)) {
            $id = $this->_id;
        } else {
            throw new Exception("The document cannot be deleted because it doesn't exist");
        }
        $disableEvents = self::$_disableEvents;
        if (!$disableEvents) {
            if (false === $this->fireEventCancel('beforeDelete')) {
                return false;
            }
        }
        if (true === $this->_skipped) {
            return true;
        }
        $connection = $this->getConnection();
        $source = $this->getSource();
        if (empty($source)) {
            throw new Exception("Method getSource() returns empty string");
        }
        /**
         * Get the \MongoCollection
         */
        $collection = $connection->selectCollection($source);

        if ('object' == gettype($id)) {
            $mongoId = $id;
        } else {
            /**
             * Is the collection using implicit object Ids?
             */
            if($this->_modelsManager->isUsingImplicitObjectIds($this)){
                $mongoId = new \MongoDB\BSON\ObjectID($id);
            }else {
                $mongoId = $id;
            }
        }
        $success = true;
        /* @var MongoDB/Collection $collection */
        $collection->findOneAndDelete([ '_id' => $mongoId ]);
        if (! $disableEvents) {
            $this->fireEvent("afterDelete");
        }
        return $success;
    }
}
