<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Base;

use Swallow\Core\Base;
use Swallow\Redis\Redis;
use Swallow\Debug\Trace;

/**
 * redis Model类
 *
 * @author  lanchuwei<lanchuwei@eelly.net>
 * @since   2016年06月13日
 * @version 1.0
 */
class RedisModel extends Base
{
    /**
     * redis类
     * @var obj
     */
    public $redis = null;
    
    /**
     * key前缀
     * @var string
     */
    private $prefix = null;
    
    /**
     * 构造
     * 
     * @author  lanchuwei<lanchuwei@eelly.net>
     * @since   2016年06月13日
     * @version 1.0
     */
    function __construct()
    {
        $this->redis = Redis::getInstance();
        $this->prefix = $this->setPrefix();
    }
    
    /**
     *  设置key前缀
     */
    public function setPrefix()
    {
        $arr = explode('\\', get_called_class());
        $model = end($arr);
        if(!strstr($model, 'RedisModel')) {
            Trace::dump('Redis模型命名空间必须包含于RedisModel！');
        }
        array_pop($arr);
        array_shift($arr);
        array_push($arr, str_replace('RedisModel', '', $model));
        $prefix = '';
        foreach ($arr as $key => $val) {
            if($val == 'Model') {
                continue;
            }
            $key > 0 && $prefix .= ':';
            $prefix .= strtolower($val);
        }
        return $prefix;
    }
    
    /**
     * 获取key前缀
     *
     * @param  string $key
     * @return mixed
     */
    public function getPrefix()
    {
        return $this->prefix;
    }
    
    /**
     * set
     *
     * @param  string $key 
     * @param  mixed  $value
     * @return bool
     */
    public function set($key, $value)
    {
        $key = $this->prefix . ':' . $key;
        return $this->redis->set($key, $value);
    }
    
    /**
     * get
     *
     * @param  string $key
     * @return mixed
     */
    public function get($key)
    {
        $key = $this->prefix . ':' . $key;
        return $this->redis->get($key);
    }
}
