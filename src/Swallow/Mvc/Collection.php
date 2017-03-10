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
                    $conn = $self->retryMongo($db['conn']);
                    return $conn->selectDB($db['conn']['db']);
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
}
