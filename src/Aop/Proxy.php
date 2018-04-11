<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Aop;

use Swallow\Core\Reflection;

/**
 * Aop  代理模块
 *      内部私有类
 * 
 * @author     SpiritTeam
 * @since      2015年1月9日
 * @version    1.0
 */
class Proxy
{

    /**
     * 创建对象
     *
     * @param  string  $class            
     * @param  array   $args            
     * @param  int     $option            
     * @return mixed
     */
    public static function getInstance($class, array $args = array(), $option = 0)
    {
        $class = Reflection::getClass($class);
        $constructor = Loader::load($class, $option);
        $ref = Reflection::getClass(self::getProxyName($class->name));
        $obj = $ref->newInstanceWithoutConstructor();
        new Joinpoint($obj);
        $constructor->invokeArgs($obj, $args);
        return $obj;
    }

    /**
     * 获取代理类
     * 
     * @param string  $className   
     * @param boolean $isDyna          
     * @return string
     */
    public static function getProxyName($className)
    {
        return 'AopProxy\\' . $className;
    }

    /**
     * 调用函数
     * 
     * @param  object   $class
     * @param  string   $methodName
     * @param  function $callback
     * @param  array    $args     
     * @param  array    $inCalleds     
     * @return mixed
     */
    public static function invoke($class, $methodName, callable $callback, array $args, array $calleds = array())
    {
        /**
         * @var $joinpoint Joinpoint
         */
        $joinpoint = $class->__JoinPoints;
        $joinpoint->setMethodName($methodName)->setArgs($args);
        \Swallow\Debug\Verify::callMethod($joinpoint);
        if (empty($calleds)) {
            return $callback($args);
        }
        if (false != $calleds['before']) {
            foreach ($calleds['before'] as $called) {
                self::isCallable($called) && call_user_func($called, $joinpoint);
            }
            $args = $joinpoint->getArgs();
        }
        if (false != $calleds['around']) {
            $joinpoint->setProcess($callback);
            $calls = 0;
            foreach ($calleds['around'] as $called) {
                if (self::isCallable($called)) {
                    $calls ++;
                    call_user_func($called, $joinpoint);
                }
            }
            //防止around 无效
            if ($calls) {
                $retval = $joinpoint->getReturnValue();
            } else {
                $retval = $callback($args);
            }
        } else {
            $retval = $callback($args);
        }
        if (false != $calleds['after']) {
            $joinpoint->setReturnValue($retval);
            foreach ($calleds['after'] as $called) {
                self::isCallable($called) && call_user_func($called, $joinpoint);
            }
            $retval = $joinpoint->getReturnValue();
        }
        return $retval;
    }

    /**
     * 是否能调用
     * 
     * @param string $func
     * @return boolean
     */
    private static function isCallable($func)
    {
        static $functions = array();
        if (! isset($functions[$func])) {
            $functions[$func] = is_callable($func);
        }
        return $functions[$func];
    }
}
