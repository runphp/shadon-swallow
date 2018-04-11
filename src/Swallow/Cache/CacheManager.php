<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Cache;

/**
 * 缓存管理器
 *
 * @author    SpiritTeam
 * @since     2015年3月10日
 * @version   1.0
 */
class CacheManager implements \Phalcon\DI\InjectionAwareInterface
{

    /**
     * var string
     */
    private $type = 'default';

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
     * 获取缓存对象
     * @todo
     * @param $cache 缓存配置
     */
    public function getServer(array $options = [], array $cache = [])
    {
        if (is_array($options)) {
            ! empty($options['type']) && $this->type = $options['type'];
        } else {
            $options && $this->type = $options;
        }
        $defaultDi = $this->getDI();
        $module = empty($options['module']) ? '' : $options['module'];
        if (! empty($module)) {
            $cacheMaster = 'cache' . $module;
            if (isset($defaultDi[$cacheMaster]) && empty($options['type'])) {
                return $defaultDi[$cacheMaster];
            }
            $file = ROOT_PATH . '/application/' . $module . '/config/' . APPLICATION_ENV . '/cache.php';
            $cache = is_file($file) ? include $file : [];
        }
        
        if (empty($cache)) {
            $config = $defaultDi->getConfig();
            $cache = $config->cache->toArray();
        }
        $backendType = $cache['backend'];
        $backend = 'Swallow\\Cache\\Backend\\' . $backendType;
        $frontend = 'Swallow\\Cache\\Frontend\\' . $cache['frontend'];
        $backendOptions = $cache[$backendType];
        if (isset($backendOptions['servers'][$this->type])) {
            $backendOptions['servers'] = $backendOptions['servers'][$this->type];
        }
        $frontCache = $defaultDi->get($frontend, [['lifetime' => $cache['lifetime']]]);
        $backCache = $defaultDi->get($backend, [$frontCache, $backendOptions]);
        return $backCache;
    }

     /**
     * 获取redis缓存对象
     *
     * @param string $type    类型
     * @param array  $config  配置
     * @author wangjiang<wangjiang@eelly.net>
     * @since  2017年4月17日
     */
    public function getRedisServer($type, array $config)
    {
        static $obj = [];
        if (!isset($obj[$type])) {
            $config = $config['Redis'][$type];
            if (is_array($config)) {
                $obj[$type] = $this->initRedis($config);
            } else {
                $obj[$type] = new \Redis();
                $arr = explode(':', $config, 2);
                $obj[$type]->connect($arr[0], $arr[1]);
            }
        }

        return $obj[$type];
    }

    /**
     * 尝试多次链接.
     *
     * @param array|string $config 配置
     * @param number       $times  尝试次数
     * @return \RedisCluster
     * @author 陈淡华<chendanhua@eelly.net>
     * @since 2016-1-9
     */
    private function initRedis($config, $times = 3)
    {
        $exception = null;
        try {
            return new \RedisCluster(null, $config['seeds'], $config['timeout'], $config['read_timeout']);
        } catch (\RedisClusterException $e) {
            $exception = $e;
        } catch (\RedisException $e) {
            $exception = $e;
        } catch (ErrorException $e) {
            $exception = $e;
        }
        if ($times > 0) {
            return self::initRedis($config, --$times);
        } else {
            throw $exception;
        }
    }
}
