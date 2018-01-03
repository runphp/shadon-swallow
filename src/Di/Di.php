<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Di;

/**
 * Di
 *
 * @author    SpiritTeam
 * @since     2015年3月10日
 * @version   1.0
 */
class Di implements DiInterface
{

    /**
     * 注册的服务列表
     * @var array
     */
    protected $services = [];

    /**
     * 服务对象列表
     * @var array
     */
    protected $sharedInstances = [];

    /**
     * 构造方法
     *
     */
    private function __construct()
    {
        $this->services = ['clientInfoNew' => new Service('clientInfoNew', "\Swallow\Business\ClientInfoNew", true)];
    }

    /**
     * 获取di单例
     * 
     * @author zengzhihao<zengzhihao@eelly.net>
     * @since  2015年12月29日
     */
    public static function getInstance()
    {
        static $instance = '';
        empty($instance) && $instance = new self();
        return $instance;
    }

    /**
     * Registers a service in the services container
     *
     * @param string name
     * @param mixed definition
     * @param boolean shared
     * @return \Swallow\Di\ServiceInterface
     */
    public function set($name, $definition, $shared = false)
    {
        $service = new Service($name, $definition, $shared);
        $this->services[$name] = $service;
        return $service;
    }

    /**
     * Registers an "always shared" service in the services container
     *
     * @param string name
     * @param mixed definition
     * @return \Swallow\Di\ServiceInterface
     */
    public function setShared($name, $definition)
    {
        $service = new Service($name, $definition, true);
        $this->services[$name] = $service;
        return $service;
    }

    /**
     * Resolves the service based on its configuration
     *
     * @param string name
     * @param array parameters
     * @return mixed
     */
    public function get($name, $parameters = null)
    {
        if (isset($this->services[$name])) {
            $service = $this->services[$name];
            $instance = $service->resolve($parameters);
        } else {
            if (! class_exists($name)) {
                throw new \Exception("Service '" . $name . "' wasn't found in the dependency injection container");
            }
            $service = new Service($name, $name);
            $instance = $service->resolve($parameters);
        }
        
        return $instance;
    }

    /**
     * Returns a shared service based on their configuration
     *
     * @param string name
     * @param array parameters
     * @return mixed
     */
    public function getShared($name, $parameters = null)
    {
        $instance = null;
        
        if (isset($this->sharedInstances[$name])) {
            $instance = $this->sharedInstances[$name];
        } else {
            $instance = $this->get($name, $parameters);
            $this->sharedInstances[$name] = $instance;
        }
        
        return $instance;
    }
}