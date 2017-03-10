<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Service;

use Swallow\Exception\SystemException;

/**
 * logic 层代理类
 *
 * @author    何辉<hehui@eely.net>
 * @since     2015年8月31日
 * @version   1.0
 */
class LogicProxy extends \Phalcon\Di\Injectable
{

    protected $activeMethod;

    /**
     * 被代理对象.
     *
     * @var Logic
     */
    protected $proxyOject;

    public function setActiveMethod($activeMethod)
    {
        $this->activeMethod = $activeMethod;
    }

    public function getActiveMethod()
    {
        return $this->activeMethod;
    }

    public function setProxyObject($proxyOject)
    {
        $this->proxyOject = $proxyOject;
        return $this;
    }

    public function getProxyObject()
    {
        return $this->proxyOject;
    }

    public function getLogicClass()
    {
        return get_class($this->proxyOject);
    }

    public function __call($method, $args = [])
    {
        if (method_exists($this->proxyOject, $method)) {
            $this->setActiveMethod($method);
            $return = $this->eventsManager->fire('logic:beforeMethod', $this, $args);
            if (false === $return) {
                $return = call_user_func_array([$this->proxyOject, $method], $args);
            }
            $return = $this->eventsManager->fire('logic:afterMethod', $this, [$args, $return]);
            return $return;
        }
        throw new SystemException("Call to undefined method '" . $method . "'");
    }
}