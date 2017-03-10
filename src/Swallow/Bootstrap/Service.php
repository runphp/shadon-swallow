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
 * 服务启动
 *
 * @author     SpiritTeam
 * @since      2015年8月13日
 * @version    1.0
 */
class Service extends BootstrapAbstract
{
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
        parent::__construct($config);
    }
}
