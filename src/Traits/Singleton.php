<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Traits;

use Swallow\Core\Reflection;

/**
 * 单例trait
 * 
 * @author     SpiritTeam
 * @since      2015年1月13日
 * @version    1.0
 */
trait Singleton
{

    /**
     * 获取参数唯一值
     *
     * @param  string $className
     * @param  array  $args
     * @return string
     */
    protected static function getStaticKey($className, array $args)
    {
        return md5($className . ':' . var_export($args, true));
    }

    /**
     * 获取单例
     *
     * @return self
     */
    public static function getInstance()
    {
        static $class = array();
        $called = get_called_class();
        $args = func_get_args();
        $key = $called::getStaticKey($called, $args); // md5($called . ':' . var_export($args, true));
        if (! isset($class[$key])) {
            $class[$key] = Reflection::getClass($called)->newInstanceArgs($args);
        }
        return $class[$key];
    }
}
