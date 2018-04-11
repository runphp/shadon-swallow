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
 * Mysqli驱动类
 *
 * @author    SpiritTeam
 * @since     2015年3月10日
 * @version   1.0
 */
class Memcache implements Cache
{

    /**
     * memcache对象
     * @var \Memcache
     */
    private $instance = null;

    /**
     * memcache 压缩级别
     * @var int
     */
    private static $flags = 3;

    /**
     * 初始化
     */
    public function __construct($server)
    {
        $this->instance = new \Memcache();
        self::$flags = isset($server['flags']);
        $persistent = isset($server['persistent']) ? $server['persistent'] : true;
        $this->instance->addServer($server['host'], $server['port'], $persistent, 1, $server['timeout']);
    }

    /**
     * 获取缓存的数据
     *
     * @param  string $key     缓存KEY
     * @return mixed
     */
    public function get($key)
    {
        return $this->instance->get($key, 0);
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
        return $this->instance->set($key, $value, 0, $time ? $time : 60);
    }

    /**
     * 添加缓存
     *
     * @param  string $key     缓存KEY
     * @param  mixed  $value   缓存的内容
     * @param  string $time    缓存时间
     * @return bool
     */
    public function add($key, $value, $time = '')
    {
        return $this->instance->add($key, $value, 0, $time ? $time : 60);
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

    /**
     * 关闭
     */
    public function __destruct()
    {
        $this->instance->close();
    }
}
