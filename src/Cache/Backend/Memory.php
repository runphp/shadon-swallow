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
 * php memory 缓存, 用于单元测试数据的校验.
 *
 * @author     SpiritTeam
 * @since      2015年8月13日
 * @version    1.0
 */
class Memory extends \Phalcon\Cache\Backend\Memory implements \Phalcon\DI\InjectionAwareInterface
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
