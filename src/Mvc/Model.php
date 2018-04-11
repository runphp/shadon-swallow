<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Mvc;

use Swallow\Traits\PublicObject;

/**
 * 模块基类
 *
 * @author     SpiritTeam
 * @since      2015年8月13日
 * @version    1.0
 */
class Model extends \Phalcon\Mvc\Model
{

    use PublicObject;

    /**
     * @var $dbService
     */
    public $dbService;

    /**
     * 创建模型对象
     *
     * @param string $params
     * @return \Phalcon\Mvc\Model\Query\BuilderInterface
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年10月13日
     */
    public function createBuilder($params = null)
    {
        $builder = $this->getModelsManager()->createBuilder($params);
        $builder->from(get_called_class());
        return $builder;
    }

    /**
     * @return static
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
        if (APP_DEBUG) {
            $verify = $defaultDi->getShared('\Swallow\Debug\VerifyBack');
            $verify->callClass($className);
        }
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
        $dbMaster = 'dbMaster';
        $dbSlave = 'dbSlave';

        $className = static::class;
        $module = strtolower(explode('\\', $className)[0]);
        if (! isset($defaultDi[$dbMaster . $module])) {
            $file = ROOT_PATH . '/application/' . $module . '/config/' . APPLICATION_ENV . '/db.php';
            if (is_file($file)) {
                $db = include $file;
                if (! empty($db) && isset($db['master']) && isset($db['slave'])) {
                    $dbMaster .= $module;
                    $dbSlave .= $module;
                    $defaultDi[$dbMaster] = function () use($db, $defaultDi)
                    {
                        $connection = $defaultDi->get('Swallow\Db\Mysql', [$db['master']]);
                        return $connection;
                    };
                    $defaultDi[$dbSlave] = function () use($db, $defaultDi)
                    {
                        $slaves = $db['slave'];
                        $randKey = array_rand($slaves, 1);
                        $connection = $defaultDi->get('Swallow\Db\Mysql', [$slaves[$randKey]]);
                        return $connection;
                    };
                }
            }
        } else {
            $dbMaster .= $module;
            $dbSlave .= $module;
        }
        $this->dbService = $dbMaster;

        $this->getModelsManager()->setDI($defaultDi);
        $this->setWriteConnectionService($dbMaster);
        $this->setReadConnectionService($dbSlave);
    }

    /**
     * 如果查询的表从库延迟了，则返回主库链接.
     *
     * @param array $intermediate
     * @param array $bindParams
     * @param array $bindTypes
     */
    public function selectReadConnection($intermediate, $bindParams, $bindTypes)
    {
        $tableNames = $intermediate['tables'];
        array_walk($tableNames,
            function (&$item)
            {
                if (is_array($item)) {
                    $item = $item[0];
                }
                $item = '\'' . md5($item) . '\'';
            });
        $sql = 'select table_name, update_time from ecm_table_sync where table_name in (' . implode(', ', $tableNames) . ')';
        $writeArr = $this->getWriteConnection()->fetchAll($sql);
        if (! $writeArr || count($writeArr) != count($tableNames)) {
            foreach ($tableNames as $value) {
                $this->getWriteConnection()->query("replace into ecm_table_sync value($value, UNIX_TIMESTAMP())");
            }
            return $this->getWriteConnection();
        }
        $readArr = $this->getReadConnection()->fetchAll($sql);
        if ($writeArr == $readArr) {
            return $this->getReadConnection();
        }
        return $this->getWriteConnection();
    }

    /* public function selectWriteConnection($intermediate, $bindParams, $bindTypes)
     {
     dd($this->getWriteConnectionService());
     dd($intermediate);
     } */

    /**
     * 返回一个包含返回结果的数组
     *
     * @param $sql
     * @param $index
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年10月29日
     */
    public function getAll($sql, $indexKey = '')
    {
        $result = $this->getWriteConnection()->fetchAll($sql);
        if (! empty($indexKey) && ! empty($result)) {
            $data = [];
            foreach ($result as $val) {
                if (is_array($indexKey)) {
                    $index = '';
                    foreach ($indexKey as $k) {
                        $index .= $index == '' ? $val[$k] : '_' . $val[$k];
                    }
                    $data[$index] = $val;
                } else {
                    $data[$val[$indexKey]] = $val;
                }
            }
            $result = $data;
        }
        return $result;
    }

    /**
     * 只返回查询结果的第一条数据
     *
     * @param $sql
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年10月29日
     */
    public function getOne($sql)
    {
        return $this->getWriteConnection()->fetchOne($sql);
    }

    /**
     * 执行
     *
     * @param $sql
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年10月29日
     */
    public function executeQuery($sql)
    {
        return $this->getWriteConnection()->query($sql);
    }

    /**
     * 返回最后插入自增id
     *
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年10月29日
     */
    public function getLastInsertId()
    {
        return $this->getWriteConnection()->lastInsertId($this->getSource());
    }

    /**
     * 返回最后sql
     *
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年10月29日
     */
    public function getLastSql()
    {
        $outputSql = $this->getDI()->getShared('Swallow\Debug\OutputSql');
        return $outputSql->getLastSql();
    }

    /**
     * 返回所有sql
     *
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年10月29日
     */
    public function getAllSql()
    {
        $outputSql = $this->getDI()->getShared('Swallow\Debug\OutputSql');
        return $outputSql->getSqlList();
    }
}
