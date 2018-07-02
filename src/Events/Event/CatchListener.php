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

namespace Swallow\Events\Event;

use Phalcon\Events\Event;
use Swallow\Annotations\AnnotationProxy;
use Swallow\Core\Log;
use Swallow\Exception\DbException;
use Swallow\Exception\LogicException;
use Swallow\Exception\SystemException;

/**
 * 转换返回数据.
 *
 * 兼容旧代码而创建，该注解已淘汰
 *
 * @deprecated
 *
 * @author hehui<hehui@eelly.net>
 */
class CatchListener extends AbstractListener
{
    public const ANNOTATION_NAME = 'catch';

    public function beforeMethod(Event $event, AnnotationProxy $object, array $data)
    {
        list($class, $method, $params) = $data;
        $collection = $this->getAnnotationCollection($class, $method);
        if (false === $collection) {
            return true;
        }
        if ($collection->has(self::ANNOTATION_NAME)) {
            $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
            $bt = end($bt);
            if (isset($bt['class'])) {
                if ((strpos($bt['class'], '\\Logic\\') && !strpos($bt['class'], 'Test'))
                    || 'Phalcon\Dispatcher' == $bt['class']
                ) {
                    return true;
                }
            }
            $returnValue = ['status' => 200, 'info' => '', 'retval' => null];
            try {
                $returnValue['retval'] = call_user_func_array([$object->_getProxyObject(), $method], $params);
            } catch (LogicException $e) {
                $returnValue['info'] = $e->getMessage();
                $returnValue['status'] = $e->getCode();
                $returnValue['retval'] = $e->getArgs();
            } catch (DbException $e) {
                $returnValue['info'] = $e->getMessage();
                $returnValue['status'] = $e->getCode();
                if (true == DEBUG_MODE) {
                    throw $e;
                } else {
                    // 这里是数据库异常的处理
                    // notice：mysqli.php 已经做了日志记录
                    Log::logWhoopException($e);
                }
            } catch (SystemException $e) {
                $returnValue['info'] = $e->getMessage();
                $returnValue['status'] = $e->getCode();
                Log::logWhoopException($e);
            }
            if (isset($e)) {
                $returnValue['throw'] = get_class($e);
                $returnValue['trace'] = $e->getTraceAsString();
            }

            $object->_setMethodReturnValue($returnValue);

            return false;
        }
    }
}
