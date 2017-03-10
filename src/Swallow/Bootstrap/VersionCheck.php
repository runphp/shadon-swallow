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
 * 相关版本要求检测
 *
 * @author     SpiritTeam
 * @since      2015年8月13日
 * @version    1.0
 */
class VersionCheck
{
    use \Swallow\Traits\InitService;

    private $config;

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
        $this->config = $config;
    }

    /**
     * 执行
     *
     * @author     SpiritTeam
     * @since      2015年8月13日
     * @version    1.0
     */
    public function run()
    {
        $errors = [];
        //php版本检测
        if (version_compare(PHP_VERSION, '5.5.0', 'lt')) {
            $errors['php'] = 'PHP 5.5.0 or higher version is required, current version is ' . PHP_VERSION;
        }
        //memcache版本检测
        if (version_compare(phpversion('memcache'), '2.2.0', 'lt')) {
            $errors['php'] = 'memecache 2.2.0 or higher version is required, current version is ' . phpversion('memcache');
        }

        if (count($errors)) {
            $msg = "Version errors: \n" . implode("\n", $errors);
            echo '<pre>' . $msg . '</pre>';
            die();
        }
    }
}
