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
 * Di接口
 *
 * @author    SpiritTeam
 * @since     2015年3月10日
 * @version   1.0
 */
interface DiInterface
{
    /**
     * Registers a service in the services container
     *
     * @param string name
     * @param mixed definition
     * @param boolean shared
     * @return \Swallow\Di\ServiceInterface
     */
    public function set($name, $definition, $shared = false);
    
    /**
     * Registers an "always shared" service in the services container
     *
     * @param string name
     * @param mixed definition
     * @return \Swallow\Di\ServiceInterface
     */
    public function setShared($name, $definition);
    
    /**
     * Resolves the service based on its configuration
     *
     * @param string name
     * @param array parameters
     * @return mixed
     */
    public function get($name, $parameters = null);
    
    /**
     * Returns a shared service based on their configuration
     *
     * @param string name
     * @param array parameters
     * @return mixed
    */
    public function getShared($name, $parameters = null);
    
}