<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Debug;

use Swallow\Toolkit\Net\Ip;

/**
 * 清楚缓存
 *
 * @author     SpiritTeam
 * @since      2015年1月9日
 * @version    1.0
 */
class ClearCache extends \Swallow\Di\Injectable
{

    /**
     * 是否强制清除缓存
     * 
     * @return bool
     */
    public function forceClearCache()
    {
        $config = $this->getDI()
            ->getConfig()
            ->toArray();
        //判断IP，只允许白名单IP进行该操作
        $ip = Ip::realIp();
        $rs = false;
        $clear = $this->getDI()->getRequest()->get('clear');
        $clear = $clear == 'cache';
        $isWhiteList = in_array($ip, $config['whiteList']);
        $isInternalUser = empty(getenv('isInternalUser')) ? false : true;
        if ($clear && (APP_DEBUG || $isWhiteList || $isInternalUser)) {
            $rs = true;
        }
        return $rs;
    }
}
