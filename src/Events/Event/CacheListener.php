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
use Swallow\Core\Conf;
use Swallow\Core\Log;

/**
 * @author    hehui<hehui@eelly.net>
 *
 * @since     2017年4月25日
 *
 * @version   1.0
 */
class CacheListener extends AbstractListener
{
    const ANNOTATION_NAME = 'MyCache';
    /**
     * @var bool 是否强制更新
     */
    protected $forceUpdate = false;
    /**
     * @var string
     */
    private $frontendClass;

    /**
     * @var \Phalcon\Cache\BackendInterface
     */
    private $backend;

    /**
     * @var array
     */
    private $backendPrefix = [
        \Phalcon\Cache\Frontend\Igbinary::class => '_I_',
        \Phalcon\Cache\Frontend\Json::class => '_J_',
        \Phalcon\Cache\Frontend\Output::class => '_O_',
    ];

    /**
     * @author hehui<hehui@eelly.net>
     *
     * @since  2017年4月26日
     */
    public function __construct()
    {
        $config = Conf::get('annotations')['listeners'][__CLASS__];
        $frontendClass = $this->frontendClass = $config['frontend'];
        $frontendOptions = $config['options'][$config['frontend']];
        $backendClass = $config['backend'];
        $backendOptions = $config['options'][$backendClass];
        $backendOptions['prefix'] = $this->backendPrefix[$frontendClass];
        $this->backend = $this->getDI()->get($backendClass, [new $frontendClass($frontendOptions), $backendOptions]);
    }

    /**
     * @return \Phalcon\Cache\BackendInterface
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since  2017年5月3日
     */
    public function getBackend()
    {
        return $this->backend;
    }

    /**
     * 获取缓存.
     *
     *
     * @param Event  $event
     * @param object $object
     * @param array  $data
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since  2017年4月25日
     */
    public function beforeMethod(Event $event, AnnotationProxy $object, array $data)
    {
        if ($this->forceUpdate) {
            return true;
        }
        list($class, $method, $params) = $data;
        $collection = $this->getAnnotationCollection($class, $method);
        if (false === $collection) {
            return true;
        }
        if ($collection->has(self::ANNOTATION_NAME)) {
            $key = $this->cacheKey($class, $method, $params);
            try {
                $value = $this->backend->get($key);
            } catch (\Exception $e) {
                Log::logWhoopException($e);

                return true;
            }
            if (empty($value)) {
                return true;
            } else {
                $object->_setMethodReturnValue($value);
                if ($event->isCancelable()) {
                    $event->stop();
                }

                return false;
            }
        }
    }

    /**
     * 添加缓存.
     *
     *
     * @param Event  $event
     * @param object $object
     * @param array  $data
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since  2017年4月25日
     */
    public function afterMethod(Event $event, AnnotationProxy $object, array $data)
    {
        list($class, $method, $params, $return) = $data;
        $collection = $this->getAnnotationCollection($class, $method);
        if (false === $collection) {
            return true;
        }
        if ($collection->has(self::ANNOTATION_NAME)) {
            $key = $this->cacheKey($class, $method, $params);
            $annotation = $collection->get(self::ANNOTATION_NAME);
            $lifetime = $annotation->getNamedParameter('lifetime');
            try {
                if (empty($lifetime)) {
                    $this->backend->save($key, $return);
                } else {
                    $this->backend->save($key, $return, $lifetime);
                }
                $this->forceUpdate = false;
            } catch (\Exception $e) {
                Log::logWhoopException($e);
            }
        }

        return true;
    }

    /**
     * 缓存key.
     *
     * @param string $class
     * @param string $method
     * @param array  $params
     *
     * @return string
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since  2017年4月26日
     */
    private function cacheKey($class, $method, array $params)
    {
        return sprintf('%s:%s:%s', $class, $method, $this->createKeyWithArray($params));
    }

    private function createKeyWithArray(array $parameters)
    {
        $uniqueKey = [];

        foreach ($parameters as $key => $value) {
            if (is_scalar($value)) {
                $uniqueKey[] = $key.':'.$value;
            } elseif (is_array($value)) {
                $uniqueKey[] = $key.':['.$this->createKeyWithArray($value).']';
            }
        }

        return implode(',', $uniqueKey);
    }
}
