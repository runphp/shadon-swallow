<?php
/*
 * PHP version 5.5
 *
 * @copyright Copyright (c) 2012-2017 EELLY Inc. (http://www.eelly.com)
 * @link      http://www.eelly.com
 * @license   衣联网版权所有
 */

namespace Swallow\Annotations;

use Closure;
use Phalcon\Di\Injectable;

/**
 * 注解代理工厂类.
 *
 * @author    hehui<hehui@eelly.net>
 *
 * @since     2017年4月22日
 *
 * @version   1.0
 */
class AnnotationProxyFactory extends Injectable
{
    /**
     * 创建代理类.
     *
     *
     * @param string   $className
     * @param \Closure $initializer
     * @param array    $proxyOptions
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since  2017年4月22日
     */
    public function createProxy($className, Closure $initializer,
        array $proxyOptions = [])
    {
        $di = $this->getDI();
        if (!$di->has($className)) {
            $di[$className] = $di->get(AnnotationProxy::class, [$this, $className, $initializer, $proxyOptions]);
        }

        return $di->getShared($className);
    }
}
