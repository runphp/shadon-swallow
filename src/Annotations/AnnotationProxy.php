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

namespace Swallow\Annotations;

use Closure;
use Phalcon\Di\Injectable;

/**
 * 注解代理类.
 *
 * @author    hehui<hehui@eelly.net>
 *
 * @since     2017年4月22日
 *
 * @version   1.0
 */
class AnnotationProxy extends Injectable
{
    /**
     * @var AnnotationProxyFactory
     */
    private $_annotionProxyFactory;

    /**
     * @var string
     */
    private $_className;
    /**
     * @var Closure
     */
    private $_initializer;

    /**
     * @var array
     */
    private $_proxyOptions;

    /**
     * @var bool
     */
    private $_isInitial = false;

    /**
     * @var object
     */
    private $_proxyObject;

    /**
     * @var mix
     */
    private $_methodReturnValue;

    /**
     * @param AnnotationProxyFactory $annotionProxyFactory
     * @param string                 $className
     * @param Closure                $initializer
     * @param array                  $proxyOptions
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since  2017年4月25日
     */
    public function __construct($annotionProxyFactory, $className, Closure $initializer, array $proxyOptions = [])
    {
        $this->_annotionProxyFactory = $annotionProxyFactory;
        $this->_className = $className;
        $this->_initializer = $initializer;
        $this->_proxyOptions = $proxyOptions;
    }

    public function __call($method, $params)
    {
        $this->_isInitial();
        $eventType = $this->_proxyOptions['eventType'];
        $bool = $this->_eventsManager->fire($eventType.':beforeMethod', $this, [$this->_className, $method, $params]);
        if (false === $bool) {
            return $this->_methodReturnValue;
        }
        $arguments = [];
        foreach ($params as &$arg) {
            $arguments[] = &$arg;
        }
        $this->_setMethodReturnValue(call_user_func_array([$this->_proxyObject, $method], $arguments));
        $data = [$this->_className, $method, $params, $this->_methodReturnValue];
        $this->_eventsManager->fire($eventType.':afterMethod', $this, $data);

        return $this->_methodReturnValue;
    }

    public function __get($property)
    {
        $this->_isInitial();

        return $this->_proxyObject->$property;
    }

    /**
     * Get proxy object.
     */
    public function _getProxyObject()
    {
        return $this->_proxyObject;
    }

    /**
     * 设置方法返回值
     *
     *
     * @param unknown $value
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since  2017年4月25日
     */
    public function _setMethodReturnValue($value): void
    {
        $this->_methodReturnValue = $value;
    }

    private function _isInitial(): void
    {
        if (!$this->_isInitial) {
            $closure = $this->_initializer;
            $this->_proxyObject = $closure();
            $di = $this->getDI();
            if (!is_object($this->_proxyObject)) {
                $this->_proxyObject = $di->getShared($this->_className);
            }
            $proxyOptions = $this->_proxyOptions;
            $this->setEventsManager($di->getShared('eventsManager'));
            $this->_isInitial = true;
        }
    }
}
