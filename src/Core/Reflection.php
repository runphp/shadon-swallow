<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Core;

/**
 * 单例反射对象
 * 
 * @author     SpiritTeam
 * @since      2015年1月12日
 * @version    1.0
 */
class Reflection
{

    /**
     * 获取单例反射对象
     *
     * @param string $className            
     * @return \ReflectionClass
     */
    public static function getClass($className)
    {
        static $class = array();
        $key = md5(print_r($className, true));
        if (! isset($class[$key])) {
            $class[$key] = new \ReflectionClass($className);
        }
        return $class[$key];
    }

    /**
     * 获取单例反射对象方法
     *
     * @param string $className            
     * @param string $methonName            
     * @return \ReflectionMethod
     */
    public static function getMethod($className, $methodName)
    {
        static $class = array();
        $key = md5(print_r($className, true) . ':' . $methodName);
        if (! isset($class[$key])) {
            $class[$key] = new \ReflectionMethod($className, $methodName);
        }
        return $class[$key];
    }
}
