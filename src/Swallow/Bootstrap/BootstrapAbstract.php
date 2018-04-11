<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */

namespace Swallow\Bootstrap;

/**
 *
 * @author     SpiritTeam
 * @since      2015年8月13日
 * @version    1.0
 */
abstract class BootstrapAbstract
{
    /**
     *
     * @var \Swallow\Config
     */
    protected $config;

    /**
     *
     * @var \Swallow\Di\FactoryDefault
     */
    protected $di;

    /**
     *
     * @var \Swallow\Mvc\Application $application
     */
    protected $application;

    /**
     * 已启动的服务列表
     *
     * @var array
     */
    protected $serviceInitedList = [];

    /**
     * 构造方法
     *
     * @param \Swallow\Config $config 配置对象
     *
     * @author    SpiritTeam
     * @since     2015年8月12日
     * @version   1.0
     */
    public function __construct(\Swallow\Config $config)
    {
        //非生产环境测试
        if ((APPLICATION_ENV & 1) == 0) {
            $versionCheck = new \Swallow\Bootstrap\VersionCheck($config);
            $versionCheck->run();
        }
        $this->config = $config;
        $this->init();
    }

    /*
     * 初始化
     *
     * @author    SpiritTeam
     * @since     2015年8月12日
     * @version   1.0
     *
     */
    abstract public function init();

    /*
     * 启动
     *
     * @author    SpiritTeam
     * @since     2015年8月12日
     * @version   1.0
     *
     */
    abstract public function run();

}
