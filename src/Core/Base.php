<?php
/*
 * PHP version 5.5
 *
 * @copyright Copyright (c) 2012-2017 EELLY Inc. (http://www.eelly.com)
 * @link      http://www.eelly.com
 * @license   衣联网版权所有
 */

namespace Swallow\Core;

use Swallow\Aop\Option;
use Swallow\Debug\Verify;
use Swallow\Traits\Interceptor;

/**
 * 模块 -> 顶层类.
 *
 * @author     SpiritTeam
 *
 * @since      2015年1月9日
 *
 * @version    1.0
 */
abstract class Base
{
    use Interceptor {getInstance as getInstanceTraits; }

    /**
     * moduleName.
     *
     * @var string
     */
    private $moduleName;

    /**
     * 获取单例.
     *
     * @return static
     */
    public static function getInstance()
    {
        $callClass = get_called_class();
        // 检查分组有没初始化过
        $group = strstr($callClass, '\\', true);
        Verify::callClass($callClass);
        if (func_num_args()) {
            $instance = call_user_func_array($callClass.'::getInstanceTraits', func_get_args());
        } else {
            $instance = $callClass::getInstanceTraits();
        }
        $instance->setModuleName($group);

        return $instance;
    }

    /**
     * 修改监听器.
     *
     * $options 参数示例
     * [\Swallow\Events\Event\CacheListener::class => ['forceUpdate' => true]]
     *
     * @param array $options
     *
     * @return self
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since 2017年5月3日
     */
    public static function specialInstance($options = [])
    {
        $di = \Phalcon\Di::getDefault();
        /**
         * @var \Swallow\Events\Manager $eventsManager
         */
        $eventsManager = $di->getShared('eventsManager');
        $calledParentClass = get_parent_class(static::class);
        $listeners = $eventsManager->getListeners($calledParentClass);
        $listeners->setExtractFlags(\SplPriorityQueue::EXTR_BOTH);
        $newListeners = [];
        foreach ($options as $key => $value) {
            while ($listeners->valid()) {
                $listen = $listeners->current();
                if ($listen['data'] instanceof $key) {
                    $listen['data']->set($value);
                }
                $newListeners[] = $listen;
                $listeners->next();
            }
        }
        foreach ($newListeners as $listen) {
            $eventsManager->attach($calledParentClass, $listen['data'], $listen['priority']);
        }

        return self::getInstance();
    }

    /**
     * 获取单例.
     *
     * @param $group
     *
     * @return self
     */
    public function setModuleName($group)
    {
        $this->moduleName = $group;
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
     * 获取模块名.
     *
     * return moduleName
     */
    public function getModuleName()
    {
        return $this->moduleName;
    }

    /**
     * Aop选项.
     *
     * @return int
     */
    protected static function getAopOption()
    {
        return Option::SKIP_PROTECTED_METHOD | Option::SKIP_PRIVATE_METHOD | Option::SKIP_PARENT_METHOD;
    }
}
