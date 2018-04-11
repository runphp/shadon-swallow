<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Cache;

use Memcached as MemcachedResource;

/**
 * Mysqli驱动类
 *
 * @author    SpiritTeam
 * @since     2015年3月10日
 * @version   1.0
 */
class Memcached implements Cache
{

    /**
     * memcache对象
     * @var \Memcached
     */
    private $instance = null;

    /**
     * 初始化
     */
    public function __construct($server)
    {
        $this->instance = new MemcachedResource();
        isset($server['flags']) && self::$flags = $server['flags'];
        $this->instance->addServer($server['host'], $server['port']);
        $this->instance->setOption(MemcachedResource::OPT_COMPRESSION, isset($server['flags']) ? ($server['flags'] > 0) : true);
        $this->instance->setOption(MemcachedResource::OPT_CONNECT_TIMEOUT, $server['timeout'] * 1000);
    }

    /**
     * 获取缓存的数据
     *
     * @param  string $key     缓存KEY
     * @return mixed
     */
    public function get($key)
    {
        return $this->instance->get($key);
    }

    /**
     * 设置缓存
     *
     * @param  string $key     缓存KEY
     * @param  mixed  $value   缓存的内容
     * @param  string $prefix  缓存KEY前缀
     * @return bool
     */
    public function set($key, $value, $time = '')
    {
        return $this->instance->set($key, $value, $time ? $time : 60);
    }

    /**
     * 并发下设置缓存.
     *
     * @param  string $key     缓存KEY
     * @param  mixed  $value   缓存的内容
     * @param  string $prefix  缓存KEY前缀
     * @return bool
     */
    public function casSet($key, $value, $time = 60)
    {
        do {
            $this->instance->get($key, null, $cas);
            if ($this->instance->getResultCode() == MemcachedResource::RES_NOTFOUND) {
                // 创建并进行一个原子添加
                $this->instance->add($key, $value);
            } else {
                // 并以cas方式去存储
                $this->instance->cas($cas, $key, $value, $time);
            }
        } while ($this->instance->getResultCode() != MemcachedResource::RES_SUCCESS);
        return true;
    }

    /**
     * 添加缓存
     *
     * @param  string $key     缓存KEY
     * @param  mixed  $value   缓存的内容
     * @param  string $prefix  缓存KEY前缀
     * @return bool
     */
    public function add($key, $value, $time = '')
    {
        return $this->instance->add($key, $value, $time ? $time : 60);
    }

    /**
     * 递增一个KEY值
     *
     * @param  string $key
     * @param  number $step   步进值
     * @return bool
     */
    function inc($key, $step = 1){
        return $this->instance->increment($key, $step);
    }

    /**
     * 递减一个KEY值
     *
     * @param  string $key
     * @param  string $prefix  缓存KEY前缀
     * @param  number $step    步进值
     * @return bool
     */
    function dec($key, $step = 1){
        return $this->instance->decrement($key, $step);
    }

    /**
     * 删除缓存
     *
     * @param  string $key     缓存KEY
     * @return bool
     */
    public function delete($key)
    {
        $this->instance->delete($key);
    }
}
