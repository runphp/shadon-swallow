<?php
/*
 * PHP version 5.5
 *
 * @copyright Copyright (c) 2012-2017 EELLY Inc. (http://www.eelly.com)
 * @link      http://www.eelly.com
 * @license   衣联网版权所有
 */

namespace Swallow\Events\Event;

use Phalcon\Di\Injectable;
use Phalcon\Events\Event;
use Swallow\Annotations\AnnotationProxy;
use Swallow\Core\Log;

abstract class AbstractListener extends Injectable
{
    /**
     * @var array
     */
    private static $collections = [];

    /**
     * @param array $values
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since  2017年5月3日
     */
    public function set(array $values)
    {
        foreach ($values as $property => $value) {
            $this->$property = $value;
        }
    }

    abstract public function beforeMethod(Event $event, AnnotationProxy $object, array $data);

    public function afterMethod(Event $event, AnnotationProxy $object, array $data)
    {
        return true;
    }

    /**
     * @param string $class
     * @param string $method
     *
     * @return \Phalcon\Annotations\Collection|bool
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since  2017年4月25日
     */
    protected function getAnnotationCollection($class, $method = null)
    {
        $key = $class.'::'.$method;
        if (isset(self::$collections[$key])) {
            return self::$collections[$key];
        }
        /**
         * @var \Phalcon\Annotations\Adapter $reader
         */
        $reader = $this->getDI()->get('annotionsReader');
        $collection = false;
        try {
            if ($method) {
                $collection = $reader->getMethod($class, $method);
            } else {
                $collection = $reader->get($class)->getClassAnnotations();
            }
        } catch (\Phalcon\Annotations\Exception $e) {
            MODULE_DEBUG && Log::alert($e->getMessage());

            $collection = false;
        }

        return self::$collections[$key] = $collection;
    }
}
