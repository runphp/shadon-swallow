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
use Swallow\Exception\DbException;
use Swallow\Exception\LogicException;
use Swallow\Exception\SystemException;
use Swallow\Annotations\Annotation;
use Swallow\Core\Log;

/**
 * 拦截器 异常捕获转换处理
 *
 * @author     SpiritTeam
 * @since      2015年3月6日
 * @version    1.0
 */
class CatchAspect
{

    /**
     * 标签名
     * @var string
     */
    const TAG = 'catch';

    /**
     * 接入类型
     * @var string
     */
    const TYPE = 'around';

    /**
     * 处理异常
     *
     * @param  Joinpoint $joinpoit
     */
    public static function run(Joinpoint $joinpoit)
    {
        $tag = Annotation::getInstance($joinpoit->getClassName())->getMethod($joinpoit->getMethodName())
            ->getAttr(self::TAG);
        if (true === $tag) {
            $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
            $bt = end($bt);
            if (isset($bt['class'])) {
                if ((strpos($bt['class'], '\\Logic\\') && !strpos($bt['class'], 'Test'))
                    || 'Phalcon\Dispatcher' == $bt['class']
                ){
                    $joinpoit->process();
                    return;
                }
            }
        }
        $retval = array('status' => 200, 'info' => '', 'retval' => null);
        try {
            DbException::$needDbException = true;
            if (! $joinpoit->isCalled()) {
               $joinpoit->process();
            }
            $retval['retval'] = $joinpoit->getReturnValue();
        } catch (LogicException $e) {
            $retval['info'] = $e->getMessage();
            $retval['status'] = $e->getCode();
            $retval['retval'] = $e->getArgs();
        } catch (DbException $e) {
            $retval['info'] = $e->getMessage();
            $retval['status'] = $e->getCode();
            if (true == DEBUG_MODE) {
                throw $e;
            } else {
                // 这里是数据库异常的处理
                // notice：mysqli.php 已经做了日志记录
                Log::logWhoopException($e);
            }
        } catch (SystemException $e) {
            $retval['info'] = $e->getMessage();
            $retval['status'] = $e->getCode();
            Log::logWhoopException($e);
        }
        if (isset($e)) {
            $retval['throw'] = get_class($e);
            $retval['trace'] = $e->getTraceAsString();
        }
        $joinpoit->setReturnValue($retval);
    }
}
