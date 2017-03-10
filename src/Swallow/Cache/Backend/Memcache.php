<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Cache\Backend;

/**
 * Memcache.
 *
 * @author     SpiritTeam
 * @since      2015年8月13日
 * @version    1.0
 */
class Memcache extends \Phalcon\Cache\Backend\Memcache implements \Phalcon\DI\InjectionAwareInterface
{

    /**
     * @var \Phalcon\DiInterface
     */
    private $di = null;

    /**
     * Sets the dependency injector
     *
     * @param mixed $dependencyInjector
     */
    public function setDI(\Phalcon\DiInterface $dependencyInjector)
    {
        $this->di = $dependencyInjector;
    }

    /**
     * Returns the internal dependency injector
     *
     * @return \Phalcon\DiInterface
     */
    public function getDI()
    {
        return $this->di;
    }

    /**
     * 连接到缓存服务器
     *
     * @author     SpiritTeam
     * @since      2015年8月13日
     * @version    1.0
     *
     */
    public function _connect()
    {
        $this->_memcache = new \Memcache();
        foreach ($this->_options['servers'] as $server) {
            $this->_memcache->addServer($server['host'], $server['port'], $server['persistent'], $server['weight'], $server['timeout']);
        }
    }

    /**
     * Returns a cached content
     *
     * @param int|string $keyName
     * @param long $lifetime
     * @return mixed
     */
    public function get($keyName, $lifetime = null)
    {
        $application = $this->di->getApplication();
        $appType = $application::APP_TYPE;
        if($appType != 'console'){
            $clearCache = $this->getDI()->getClearCache()->forceClearCache();
            if ($clearCache === true) {
                return false;
            }
        }
        return parent::get($keyName, $lifetime);
    }
}