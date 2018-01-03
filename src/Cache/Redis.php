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
 * Redis Cache驱动类
 * 
 * @author    陈淡华<chendanhua@eelly.net>
 * @since     2016-1-9
 * @version   1.0
 */
class Redis implements Cache
{

    /**
     * redis对象
     * @var \Memcache
     */
    private $instance = null;

    /**
     * 初始化
     */
    public function __construct($server)
    {
        $this->instance = \Swallow\Redis\Redis::getInstance();
    }

    /**
     * 获取缓存的数据
     *
     * @param  string $key     缓存KEY
     * @return mixed
     */
    public function get($key)
    {
        $value = $this->instance->get($key);
        return json_decode(gzuncompress($value), 1);
    }

    /**
     * 设置缓存
     *
     * @param  string $key     缓存KEY
     * @param  mixed  $value   缓存的内容
     * @param  integer $time   缓存时间
     * @return bool
     */
    public function set($key, $value, $time = 0)
    {
        $value = gzcompress(json_encode($value));
        if ($time > 0) {
            return $this->instance->setex($key, $time, $value);
        }
        return $this->instance->set($key, $value);
    }

    /**
     * 添加缓存
     *
     * @param  string $key     缓存KEY
     * @param  mixed  $value   缓存的内容
     * @param  integer $time   缓存时间
     * @return bool
     */
    public function add($key, $value, $time = 0)
    {
        if ($this->instance->exists($key)) {
            return false;
        }
        return $this->set($key, $value, $time);
    }

    /**
     * 递增一个KEY值
     *
     * @param  string $key
     * @param  number $step   步进值
     * @return bool
     */
    function inc($key, $step = 1){
        return $this->instance->incr($key, $step);
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
        return $this->instance->decr($key, $step);
    }

    /**
     * 删除缓存
     *
     * @param  string $key     缓存KEY
     * @return bool
     */
    public function delete($key)
    {
        $this->instance->del($key);
    }

    /**
     * 关闭
     */
    public function __destruct()
    {
        $this->instance->close();
    }
}
