<?php
/*
 * PHP version 5.5
 *
 * @copyright Copyright (c) 2012-2017 EELLY Inc. (http://www.eelly.com)
 * @link      http://www.eelly.com
 * @license   衣联网版权所有
 */

namespace Swallow\Events\Event;

use Phalcon\Events\Event;
use Swallow\Annotations\AnnotationProxy;
use Swallow\Core\Log;

/**
 * @author    hehui<hehui@eelly.net>
 *
 * @since     2017年4月28日
 *
 * @version   1.0
 */
class DeprecatedListener extends AbstractListener
{
    /**
     * @param Event           $event
     * @param AnnotationProxy $object
     * @param array           $data
     *
     * @return bool
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since  2017年4月28日
     */
    public function beforeMethod(Event $event, AnnotationProxy $object, array $data)
    {
        list($class, $method) = $data;
        $collection = $this->getAnnotationCollection($class);
        if (false === $collection) {
            return true;
        }
        if ($collection->has('deprecated')) {
            Log::alert(sprintf('类%s已废弃，请清理代码', $class));

            return true;
        }
        $collection = $this->getAnnotationCollection($class, $method);
        if (false === $collection) {
            return true;
        }
        if ($collection->has('deprecated')) {
            Log::alert(sprintf('类%s的%s方法已废弃，请清理代码', $class, $method));

            return true;
        }
    }
}
