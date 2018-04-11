<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Aop\Aspect;

use Swallow\Aop\Joinpoint;
use Swallow\Core\Mq;
use Swallow\Annotations\Annotation;

/**
 * 拦截器 异步调用处理类
 *
 * @author     SpiritTeam
 * @since      2015年3月6日
 * @version    1.0
 */
class AsyncAspect
{

    /**
     * 标签名
     * @var string
     */
    const TAG = 'async';

    /**
     * 接入类型
     * @var string
     */
    const TYPE = 'around';

    /**
     * 执行异步处理
     *
     * @param  Joinpoint $joinpoit
     */
    public static function run(Joinpoint $joinpoit)
    {
        if (! class_exists('AMQPConnection')) {
            return $joinpoit->process();
        }

        if (defined('IN_CONSUME')) {
            $joinpoit->setReturnValue($joinpoit->process());
            return;
        }
        $class = $joinpoit->getClassName();
        $method = $joinpoit->getMethodName();
        $param = $joinpoit->getArgs();
        $routingKey = Annotation::getInstance($class)->getMethod($method)->getAttr(self::TAG);
        if (true === $routingKey) {
            Mq::getInstance()->send($class . '::' . $method, $param);
        } else {
            Mq::getInstance()->send($class . '::' . $method, $param, $routingKey);
        }
        $joinpoit->setProcessCalled(true);
        $joinpoit->setReturnValue(true);
    }
}
