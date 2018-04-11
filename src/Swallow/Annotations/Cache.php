<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Annotations;

/**
 * Annotation缓存类
 *
 * @author     SpiritTeam
 * @since      2015年1月15日
 * @version    1.0
 */
class Cache
{

    /**
     * 缓存的键值
     * @var string
     */
    public static $cacheKey = '';

    /**
     * 缓存的键值
     * @var string
     */
    public static $cacheTime = 60;

    /**
     * 缓存读出来的数据字串符
     * @var array
     */
    private static $loaded = array();

    /**
     * 缓存数据
     * @var array
     */
    private static $cached = array();

    /**
     * 缓存的对象
     * @var \Swallow\Core\Cache
     */
    public static $cacheObj = null;

    /**
     * 读取缓存
     *
     * @param  string $key
     * @return boolean|array
     */
    public static function get($key)
    {
        if (isset(self::$cached[$key])) {
            return self::$cached[$key];
        }
        $cacheKey = self::$cacheKey . '_' . md5($key);
        self::$cached[$key] = self::$loaded[$key] = self::$cacheObj->get($cacheKey);
        self::$cached[$key] = empty(self::$cached[$key]) ? array() : unserialize(self::$cached[$key]);
        self::$cached[$key] = empty(self::$cached[$key]) ? array() : self::$cached[$key];
        //echo 'annotations cache get : ' . $key . '  ' . $cacheKey . var_export(self::$cached[$key], true);
        return empty(self::$cached[$key]) ? false : self::$cached[$key];
    }

    /**
     * 设置缓存
     *
     * @param string $key
     * @param array  $value
     */
    public static function set($key, $value)
    {
        self::$cached[$key] = $value;
    }
    
    /**
     * 删除缓存
     *
     * @param string $key
     */
    public static function delete($key)
    {
        $cacheKey = self::$cacheKey . '_' . md5($key);
        return self::$cacheObj->delete($cacheKey);
    }

    /**
     * 保存缓存
     */
    public static function save()
    {
        foreach (self::$cached as $key => $cache) {
            $cacheKey = self::$cacheKey . '_' . md5($key);
            $serialize = serialize($cache);
            //echo 'annotations cache save : ' . $key . '  ' . $cacheKey . ' time ' . self::$cacheTime . ' data ' . $serialize;
            if (empty(self::$loaded[$key])) {
                self::$cacheObj->add($cacheKey, $serialize, self::$cacheTime);
            } elseif (self::$loaded[$key] != $serialize) {
                self::$cacheObj->casSet($cacheKey, $serialize, self::$cacheTime);
            }
        }
    }
}