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

namespace Swallow\Mvc;

use MongoDB\BSON\ObjectID;
use MongoDB\BSON\Unserializable;
use MongoDB\Collection as AdapterCollection;
use MongoDB\Driver\WriteConcern;
use MongoDB\InsertOneResult;
use Phalcon\Di;
use Phalcon\Mvc\Collection as PhalconCollection;
use Phalcon\Mvc\Collection\Document;
use Phalcon\Mvc\Collection\Exception;
use Phalcon\Mvc\CollectionInterface;
use Swallow\Core\Log;
use Swallow\Mongodb\Exception\MongoDuplicateKeyException;

/**
 * class Collection for MongoDB.
 *
 * @property  \Phalcon\Mvc\Collection\ManagerInterface _modelsManager
 *
 * @method \MongoDB\Database getConnection()
 *
 * @author hehui<hehui@eelly.net>
 *
 * @since 2016年10月3日
 *
 * @version 1.0
 */
abstract class Collection extends PhalconCollection implements Unserializable
{
    // @codingStandardsIgnoreStart
    protected static $_disableEvents;

    /**
     * 转换数组到对象
     *
     *
     * @param array $data
     *
     * @return \Swallow\Mvc\Collection
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since 2016年10月5日
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
     *
     * @return number|mixed
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since 2016年10月5日
     */
    public static function getMaxValue($field)
    {
        $clazz = get_called_class();
        $result = $clazz::aggregate([
            '$group' => [
                '_id'       => '',
                'max_value' => [
                    '$max' => "\$$field",
                ],
            ],
        ]);
        $it = new \IteratorIterator($result);
        $it->rewind();
        $maxValue = $it->current()['max_value'];

        return $maxValue;
    }

    /**
     * (non-PHPdoc).
     *
     * @see \Phalcon\Mvc\Collection::save()
     */
    public function save()
    {
        if (version_compare(PHP_VERSION, '7.1.0') >= 0) {
            $this->_dependencyInjector = \Phalcon\Di::getDefault();
            $this->_modelsManager = $this->_dependencyInjector->getShared('collectionManager');
            $this->_modelsManager->initialize($this);
        }
        $collection = $this->prepareCU();
        $exists = $this->_exists($collection);
        if (false === $exists) {
            $this->_operationMade = self::OP_CREATE;
        } else {
            $this->_operationMade = self::OP_UPDATE;
        }
        /*
         * The messages added to the validator are reset here
         */
        $this->_errorMessages = [];
        $disableEvents = self::$_disableEvents;
        /*
         * Execute the preSave hook
         */
        if (false === $this->_preSave($this->_dependencyInjector, $disableEvents, $exists)) {
            return false;
        }
        $data = $this->toArray();
        /*
         * We always use safe stores to get the success state
         * Save the document
         */
        switch ($this->_operationMade) {
            case self::OP_CREATE:
                try {
                    $status = $collection->insertOne($data);
                } catch (\MongoDB\Driver\Exception\BulkWriteException $e) {
                    if (11000 == $e->getWriteResult()->getWriteErrors()[0]->getCode()) {
                        throw new MongoDuplicateKeyException($e->getMessage(), 11000, $e);
                    } else {
                        throw $e;
                    }
                }
                break;
            case self::OP_UPDATE:
                $status = $collection->updateOne([
                    '_id' => $this->_id,
                ], [
                    '$set' => $this->toArray(),
                ]);
                break;
            default:
                throw new Exception('Invalid operation requested for '.__METHOD__);
        }
        $success = false;
        if ($status->isAcknowledged()) {
            $success = true;
            if (false === $exists) {
                $this->_id = $status->getInsertedId();
            }
        }
        /*
         * Call the postSave hooks
         */
        return $this->_postSave($disableEvents, $success, $exists);
    }

    /**
     * (non-PHPdoc).
     *
     * @see \Phalcon\Mvc\Collection::findById()
     *
     * @param mixed $id
     */
    public static function findById($id)
    {
        if (!is_object($id)) {
            $classname = get_called_class();
            $collection = new $classname();
            /** @var Collection $collection */
            if ($collection->getCollectionManager()->isUsingImplicitObjectIds($collection)) {
                $mongoId = new ObjectID($id);
            } else {
                $mongoId = $id;
            }
        } else {
            $mongoId = $id;
        }

        return static::findFirst([
            [
                '_id' => $mongoId,
            ],
        ]);
    }

    /**
     * @param array $parameters
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since 2017年3月14日
     */
    public static function aggregate(array $parameters = null, array $options = null) : array
    {
        $result = parent::aggregate($parameters, $options);
        $return = [
            'result' => $result->toArray(),
        ];

        return $return;
    }

    /**
     * (non-PHPdoc).
     *
     * @see \Phalcon\Mvc\Collection::delete()
     */
    public function delete()
    {
        if (!$id = $this->_id) {
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
            throw new Exception('Method getSource() returns empty string');
        }
        /**
         * Get the Collection.
         *
         * @var AdapterCollection
         */
        $collection = $connection->selectCollection($source);
        if (is_object($id)) {
            $mongoId = $id;
        } else {
            if ($this->_modelsManager->isUsingImplicitObjectIds($this)) {
                $mongoId = new ObjectID($id);
            } else {
                $mongoId = $id;
            }
        }
        $success = false;
        /**
         * Remove the instance.
         */
        $status = $collection->deleteOne([
            '_id' => $mongoId,
        ], [
            'w' => true,
        ]);
        if ($status->isAcknowledged()) {
            $success = true;
            if (!$disableEvents) {
                $this->fireEvent('afterDelete');
            }
        }

        return $success;
    }

    /**
     * (non-PHPdoc).
     *
     * @see \Phalcon\Mvc\Collection::setId()
     *
     * @param mixed $id
     */
    public function setId($id): void
    {
        if (is_object($id)) {
            $this->_id = $id;

            return;
        }
        if ($this->_modelsManager->isUsingImplicitObjectIds($this)) {
            $this->_id = new ObjectID($id);

            return;
        }
        $this->_id = $id;
    }

    /**
     * 获取collection.
     *
     * @return \MongoDB\Collection
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since 2017年3月23日
     */
    public static function getCollection()
    {
        $className = get_called_class();
        $collection = new $className();

        return $collection->prepareCU();
    }

    /**
     * (non-PHPdoc).
     *
     * @see \Phalcon\Mvc\Collection::summatory()
     *
     * @param string $field
     * @param mixed  $conditions
     * @param mixed  $finalize
     *
     * @throws Exception
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since 2017年4月6日
     */
    public static function summatory($field, $conditions = null, $finalize = null): void
    {
        throw new Exception('The summatory() method is not implemented in the new Mvc Collection');
    }

    public function create()
    {
        /* @var AdapterCollection $collection */
        $collection = $this->prepareCU();
        /*
         * Check the dirty state of the current operation to update the current operation
         */
        $this->_operationMade = self::OP_CREATE;
        /*
         * The messages added to the validator are reset here
         */
        $this->_errorMessages = [];
        /*
         * Execute the preSave hook
         */
        if (false === $this->_preSave($this->_dependencyInjector, self::$_disableEvents, false)) {
            return false;
        }
        $data = $this->toArray();
        $success = false;
        /**
         * We always use safe stores to get the success state
         * Save the document.
         */
        $result = $collection->insert($data, [
            'writeConcern' => new WriteConcern(1),
        ]);
        if ($result instanceof InsertOneResult && $result->getInsertedId()) {
            $success = true;
            $this->_id = $result->getInsertedId();
        }
        /*
         * Call the postSave hooks
         */
        return $this->_postSave(self::$_disableEvents, $success, false);
    }

    /**
     * @param array $data
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since 2017年4月6日
     */
    public function bsonUnserialize(array $data): void
    {
        $this->setDI(Di::getDefault());
        $this->_modelsManager = Di::getDefault()->getShared('collectionManager');
        foreach ($data as $key => $val) {
            $this->{$key} = $val;
        }
        if (method_exists($this, 'afterFetch')) {
            $this->afterFetch();
        }
    }

    // @codingStandardsIgnoreEnd

    /**
     * mongo库选择.
     *
     * @param string $db
     *                   databse name
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since 2016年10月4日
     */
    protected function selectDb($db)
    {
        $di = $this->getDI();
        if (!$di->has('mongo_db_'.$db)) {
            $di->set('mongo_db_'.$db, function () use ($di, $db) {
                $mongoClient = $di->has('mongo_'.$db) ? $di->get('mongo_'.$db) : $di->get('mongo_default');
                /* @var \MongoDB\Client $mongoClient */
                $database = $mongoClient->selectDatabase($db);

                return $database;
            }, true);
        }

        return $this->setConnectionService('mongo_db_'.$db);
    }

    /**
     * (non-PHPdoc).
     *
     * @see \Phalcon\Mvc\Collection::_exists() @codingStandardsIgnoreStart
     *
     * @param mixed $collection
     */
    protected function _exists($collection)
    {
        // @codingStandardsIgnoreEnd
        if (!$id = $this->_id) {
            return false;
        }

        if (is_object($id)) {
            $mongoId = $id;
        } else {
            /*
             * Check if the model use implicit ids
             */
            if ($this->_modelsManager->isUsingImplicitObjectIds($this)) {
                $mongoId = new ObjectID($id);
            } else {
                $mongoId = $id;
            }
        }
        /*
         * Perform the count using the function provided by the driver
         */
        return $collection->count([
            '_id' => $mongoId,
        ]) > 0;
    }

    /**
     * (non-PHPdoc).
     *
     * @see \Phalcon\Mvc\Collection::prepareCU()
     */
    protected function prepareCU()
    {
        $dependencyInjector = $this->_dependencyInjector;
        if (!is_object($dependencyInjector)) {
            throw new Exception('A dependency injector container is required to obtain the services related to the ODM');
        }
        $source = $this->getSource();
        if (empty($source)) {
            throw new Exception('Method getSource() returns empty string');
        }
        $connection = $this->getConnection();
        /**
         * Choose a collection according to the collection name.
         */
        $collection = $connection->selectCollection($source);

        return $collection;
    }

    /**
     * Returns a collection resultset for mongodb.
     *
     * @param array               $params
     * @param CollectionInterface $collection
     * @param \MongoDB\Database   $connection
     * @param bool                $unique
     *
     * @throws Exception
     *
     * @return mixed|unknown[]
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since 2017年4月6日
     */
    protected static function _getResultset($params, CollectionInterface $collection, $connection, $unique)
    {
        /*
         * @codingStandardsIgnoreEnd
         * Check if "class" clause was defined
         */
        if (isset($params['class'])) {
            $classname = $params['class'];
            $base = new $classname();

            if (!$base instanceof CollectionInterface || $base instanceof Document) {
                throw new Exception(sprintf('Object of class "%s" must be an implementation of %s or an instance of %s', get_class($base), CollectionInterface::class, Document::class));
            }
        } else {
            $base = $collection;
        }

        $source = $collection->getSource();
        if (empty($source)) {
            throw new Exception('Method getSource() returns empty string');
        }
        /**
         * @var \MongoDB\Collection
         */
        $mongoCollection = $connection->selectCollection($source);
        if (!is_object($mongoCollection)) {
            throw new Exception("Couldn't select mongo collection");
        }
        $conditions = [];
        if (isset($params[0]) || isset($params['conditions'])) {
            $conditions = (isset($params[0])) ? $params[0] : $params['conditions'];
        }
        /*
         * Convert the string to an array
         */
        if (!is_array($conditions)) {
            throw new Exception('Find parameters must be an array');
        }
        $options = [];
        /*
         * Check if a "limit" clause was defined
         */
        if (isset($params['limit'])) {
            $limit = $params['limit'];
            $options['limit'] = (int) $limit;
            if ($unique) {
                $options['limit'] = 1;
            }
        }
        /*
         * Check if a "sort" clause was defined
         */
        if (isset($params['sort'])) {
            $sort = $params['sort'];
            $options['sort'] = $sort;
        }
        /*
         * Check if a "skip" clause was defined
         */
        if (isset($params['skip'])) {
            $skip = $params['skip'];
            $options['skip'] = (int) $skip;
        }
        if (isset($params['fields']) && is_array($params['fields']) && !empty($params['fields'])) {
            $options['projection'] = [];
            foreach ($params['fields'] as $key => $show) {
                $options['projection'][$key] = $show;
            }
        }
        Log::pushCustomFatalInfo(['lastMql'=> [(array) $conditions, $options]]);
        $startTime = time();
        /**
         * Perform the find.
         */
        $cursor = $mongoCollection->find($conditions, $options);
        $used = time() - $startTime;
        $slowTime = $collection->getCollectionManager()->getSlowLogTime($mongoCollection->getDatabaseName());
        if ($slowTime <= $used) {
            Log::warning('slow mongodb query', ['collection' => $source, 'query' => ['$conditions' => $conditions, '$options' => $options], 'used' => $used.'s']);
        }

        $cursor->setTypeMap([
            'root'     => get_class($base),
            'document' => 'array',
        ]);
        if (true === $unique) {
            /*
             * Looking for only the first result.
             */
            return current($cursor->toArray());
        }
        /**
         * Requesting a complete resultset.
         */
        $collections = [];
        foreach ($cursor as $document) {
            /*
             * Assign the values to the base object
             */
            $collections[] = $document;
        }

        return $collections;
    }
}
