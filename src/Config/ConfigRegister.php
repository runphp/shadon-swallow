<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Config;

class ConfigRegister implements \Swallow\Bootstrap\BootstrapInterface
{

    protected $di;

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

    public function bootStrap()
    {
        //配置文件初始化
        $config = include "config/config.php";
        $this->di->setShared('config', function () use($config)
        {
            return new \Swallow\Config\Config($config);
        });
    }
}