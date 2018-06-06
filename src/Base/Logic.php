<?php

declare(strict_types=1);

/*
 * This file is part of eelly package.
 *
 * (c) eelly.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swallow\Base;

use Swallow\Annotations\AnnotationProxyFactory;
use Swallow\Core\Base;
use Swallow\Debug\Verify;
use Swallow\Di\Di;

/**
 * 逻辑业务基类
 * 逻辑业务的编写.
 *
 * @author     SpiritTeam
 *
 * @since      2015年1月12日
 *
 * @version    1.0
 */
abstract class Logic extends Base
{
    /**
     * 是否通过服务访问数据.
     *
     * > 迁移完毕的逻辑层请用true覆盖
     *
     * @var bool
     */
    public const IS_EXIST_SERVICE = false;

    /**
     * 构造器.
     */
    final protected function __construct()
    {
        if (func_num_args()) {
            call_user_func_array([$this, 'init'], func_get_args());
        } else {
            $this->init();
        }
    }

    /**
     * 获取单例.
     *
     * @return static
     */
    public static function getInstance()
    {
        $calledClass = static::class;
        // 校验
        Verify::callClass($calledClass);
        $calledParentClass = get_parent_class($calledClass);
        $di = \Phalcon\Di::getDefault();
        /**
         * @var \Swallow\Annotations\AnnotationProxyFactory $annotationProxyFactory
         */
        $annotationProxyFactory = $di->getShared(AnnotationProxyFactory::class);
        $args = func_get_args();

        $proxyObject = $annotationProxyFactory->createProxy($calledClass, function () use ($args, $calledClass) {
            $group = strstr($calledClass, '\\', true);
            $reflectionClass = new \ReflectionClass($calledClass);
            $instance = $reflectionClass->newInstanceWithoutConstructor();
            $constructor = $reflectionClass->getConstructor();
            if (!$constructor->isPublic()) {
                $constructor->setAccessible(true);
            }
            $constructor->invokeArgs($instance, $args);
            $instance->setModuleName($group);

            return $instance;
        }, [
            'eventType' => $calledParentClass,
        ]);

        return $proxyObject;
    }

    /**
     * 初始化.
     */
    protected function init(): void
    {
    }

    /**
     * 判断是否清除缓存.
     *
     * @return bool
     */
    protected function isClearCache()
    {
        static $r = null;
        if (isset($r)) {
            return $r;
        }
        if ('cache' == Di::getInstance()->getShared('clientInfoNew')->getClearCache() && (DEBUG_MODE || $_ENV['isInternalUser'])) {
            $r = true;
        } else {
            $r = false;
        }

        return $r;
    }
}
