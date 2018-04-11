<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Core;

use Swallow\Core\Conf;
use Swallow\Toolkit\Util\Strings;

/**
 * 缓存类
 *
 * @author     SpiritTeam
 * @since      2015年1月9日
 * @version    1.0
 */
class Cache
{

    /**
     * 缓存实例
     * @var \Swallow\Cache
     */
    private $cache;

    /**
     * 配置
     * @var array
     */
    private $conf = array();

    /**
     * 缓存数组配置
     * @var array
     */
    private $cacheStore = array();

    /**
     * 模块
     * @var Strings
     */
    private static $module;

    /**
     * 获取db类
     *
     * @return self
     */
    public static function getInstance($module = '')
    {
        self::$module = $module ? ucfirst($module) : 'Swallow';
        static $obj = null;
        if (isset($obj)) {
            return $obj;
        }
        $obj = new self();
        return $obj;
    }

    /**
     *
     * 获取Memcached
     *
     *
     * @return CacheServer
     * @author 何辉<hehui@eely.net>
     * @since  2015年5月16日
     */
    public function getMemcached()
    {
        return cache_server()->getInstance()->instance;
    }

    /**
     * 构造
     */
    public function __construct()
    {
        $this->conf = Conf::get('Swallow/cache');
        $this->cacheStore[self::$module] = Conf::get(self::$module.'/cachestore');
    }

    /**
     * 跟新缓存配置
     */
    public function updateCacheStore()
    {
         !isset($this->cacheStore[self::$module]) && $this->cacheStore[self::$module] = Conf::get(self::$module.'/cachestore');
    }

    /**
     * 获取memcache连接
     *
     * @param  string $cache
     * @return int
     */
    private function setLink($prefix)
    {
        $serverConf = $this->conf['server']['default'];
        $time = 1;
        if (is_numeric($prefix)) {
            $time = intval($prefix);
        } elseif (! empty($prefix)) {
            if (strpos($prefix, '.')) {
                list (self::$module, $prefix) = explode('.', $prefix, 2);
            }
        }
        $this->updateCacheStore();
        if (isset($this->cacheStore[self::$module][$prefix])) {
            if (isset($this->cacheStore[self::$module][$prefix]['store'])) {
                $serverConf = $this->conf['server'][$this->cacheStore[self::$module][$prefix]['store']];
            }
            if (isset($this->cacheStore[self::$module][$prefix]['ttl'])) {
                $time = intval($this->cacheStore[self::$module][$prefix]['ttl']);
            }
        }
        static $cacheServers = [];
        $key = $serverConf['host'] . ':' . $serverConf['port'];
        if (! isset($cacheServers[$key])) {
            $class = '\\Swallow\\Cache\\' . ucfirst($this->conf['type']);
            $serverConf['timeout'] = $this->conf['timeout'];
            $cacheServers[$key] = new $class($serverConf);
        }
        $this->cache = $cacheServers[$key];

        return $time;
    }

    /**
     * 获取缓存的数据
     *
     * @param  string $key     缓存KEY
     * @param  string $prefix  缓存KEY前缀
     * @return mixed
     */
    public function get($key, $prefix = '')
    {
        $time = $this->setLink($prefix);
        try {
            $result = $this->cache->get($key);
            return $result;
        } catch (\Exception $e) {
            $this->delete($key, $prefix);
            return false;
        }
    }

    /**
     * 设置缓存
     *
     * @param  string $key     缓存KEY
     * @param  mixed  $value   缓存的内容
     * @param  string $prefix  缓存KEY前缀
     * @return bool
     */
    public function set($key, $value, $prefix = '')
    {
        $time = $this->setLink($prefix);
        return $this->cache->set($key, $value, $time);
    }

    /**
     * 并发设置缓存.
     *
     * @param  string $key     缓存KEY
     * @param  mixed  $value   缓存的内容
     * @param  string $prefix  缓存KEY前缀
     * @return bool
     */
    public function casSet($key, $value, $prefix = '')
    {
        if (method_exists($this->cache, 'casSet')) {
            $time = $this->setLink($prefix);
            return $this->cache->casSet($key, $value, $time);
        }else {
            return $this->set($key, $value, $prefix);
        }
    }

    /**
     * 添加缓存
     *
     * @param  string $key     缓存KEY
     * @param  mixed  $value   缓存的内容
     * @param  string $prefix  缓存KEY前缀
     * @return bool
     */
    public function add($key, $value, $prefix = '')
    {
        $time = $this->setLink($prefix);
        return $this->cache->add($key, $value, $time);
    }

    /**
     * 删除缓存
     *
     * @param  string $key     缓存KEY
     * @param  string $prefix  缓存KEY前缀
     * @return bool
     */
    public function delete($key, $prefix = '')
    {
        $time = $this->setLink($prefix);
        return $this->cache->delete($key);
    }

    /**
     * 递增一个KEY值
     *
     * @param  string $key
     * @param  number $step   步进值
     * @param  string $prefix  缓存KEY前缀
     * @return bool
     */
    public function inc($key, $prefix = '', $step = 1)
    {
        $time = $this->setLink($prefix);
        return $this->cache->inc($key, $step);
    }

    /**
     * 递减一个KEY值
     *
     * @param  string $key
     * @param  number $step   步进值
     * @param  string $prefix  缓存KEY前缀
     * @return bool
     */
    public function dec($key, $prefix = '', $step = 1)
    {
        $time = $this->setLink($prefix);
        return $this->cache->dec($key, $step);
    }
}