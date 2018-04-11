<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Core;

use Swallow\Debug\Verify;
use Swallow\Traits\Interceptor;
use Swallow\Aop\Option;

/**
 * 模块 -> 顶层类
 *
 * @author     SpiritTeam
 * @since      2015年1月9日
 * @version    1.0
 */
abstract class Base
{
    use Interceptor {getInstance as getInstanceTraits;}

    /**
     * 装载已初始化过的分组
     * @var array
     */
    private static $iniGroup = array();

    /**
     * moduleName.
     *
     * @var string
     */
    private  $moduleName;
    
    /**
     * 获取单例
     *
     * @return self
     */
    public static function getInstance()
    {
        $callClass = get_called_class();
        // 检查分组有没初始化过
        $group = strstr($callClass, '\\', true);
        self::iniGroup($group);
        Verify::callClass($callClass);
        if (func_num_args()) {
            $instance =  call_user_func_array($callClass . '::getInstanceTraits', func_get_args());
        } else {
            $instance = $callClass::getInstanceTraits();
        }
        $instance->setModuleName($group);
        return $instance;
    }

    /**
     * 获取单例
     *
     * @param $group
     * @return self
     */
    public function setModuleName($group)
    {
        $this->moduleName = $group;
    }
    /**
     * Aop选项
     *
     * @return int
     */
    protected static function getAopOption()
    {
        return Option::SKIP_PROTECTED_METHOD | Option::SKIP_PRIVATE_METHOD | Option::SKIP_PARENT_METHOD;
    }

    /**
     * 初始化分组
     *
     * @param string $group
     */
    protected static function iniGroup($group)
    {
        if (! isset(self::$iniGroup[$group])) {
            self::$iniGroup[$group] = true;
            $call = '\\' . $group . '\\Init::start';
            is_callable($call) && call_user_func($call);
        }
    }

    /**
     * 获取缓存.
     *
     * return cache
     */
    public function getCache()
    {
        return Cache::getInstance($this->moduleName);
    }
    
    /**
     * 获取模块名
     *
     * return moduleName
     */
    public function getModuleName()
    {
        return $this->moduleName;
    }
}
