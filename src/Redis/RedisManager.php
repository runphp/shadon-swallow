<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Redis;

/**
 * 缓存管理器
 *
 * @author    SpiritTeam
 * @since     2015年3月10日
 * @version   1.0
 */
class RedisManager implements \Phalcon\DI\InjectionAwareInterface
{

    /**
     * @var \Phalcon\DiInterface
     */
    private $di = null;

    /**
     * Sets the dependency injector
     *
     * @param mixed $dependencyInjector
     */
    public function setDI(\Phalcon\DiInterface $dependencyInjector)
    {
        $this->di = $dependencyInjector;
    }

    /**
     * Returns the internal dependency injector
     *
     * @return \Phalcon\DiInterface
     */
    public function getDI()
    {
        return $this->di;
    }

    /**
     * 获取对象
     * @todo
     * @param $redis 配置
     */
    public function getServer(array $options = [], array $redis = [])
    {
        $defaultDi = $this->getDI();
        $module = empty($options['module']) ? '' : $options['module'];
        if (! empty($module)) {
            $redisMaster = 'redis' . $module;
            if (isset($defaultDi[$redisMaster])) {
                return $defaultDi[$redisMaster];
            }
            $file = ROOT_PATH . '/application/' . $module . '/config/' . APPLICATION_ENV . '/config.php';
            $config = is_file($file) ? include $file : [];
            $redis = ! empty($config['redis']) ? $config['redis'] : [];
        }
        if (empty($redis)) {
            $config = $defaultDi->getConfig();
            $redis = $config->redis->toArray();
        }
        $host = ! empty($redis['host']) ? $redis['host'] : '127.0.0.1';
        $port = ! empty($redis['port']) ? $redis['port'] : 6379;
        
        $redisLink = new \Redis();
        $redisLink->connect($host, $port);
        return $redisLink;
    }
}
