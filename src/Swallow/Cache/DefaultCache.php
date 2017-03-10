<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Cache;

/**
 * 缓存
 *
 * @author    SpiritTeam
 * @since     2015年3月10日
 * @version   1.0
 */
class DefaultCache
{

    /**
     * cache配置
     * @var array
     */
    protected $config = [];
    
    /**
     * 构造
     * 
     * @author zengzhihao<zengzhihao@eelly.net>
     * @since  2015年12月29日
     */
    public function __construct()
    {
        $this->config = \Phalcon\Di::getDefault()->getConfig()->cache->toArray();
    }
    
    /**
     * 获取默认缓存对象
     * 
     * @author zengzhihao<zengzhihao@eelly.net>
     * @since  2015年11月30日
     */
    public function getCache()
    {
        return \Phalcon\Di::getDefault()->get('cacheManager')->getServer([], $this->config);
    }

    /**
     * 获取旧商城登陆cache
     * 
     * @author zengzhihao<zengzhihao@eelly.net>
     * @since  2015年12月29日
     */
    public function getLoginCache()
    {
        return \Phalcon\Di::getDefault()->get('cacheManager')->getServer(['type'=>'login'], $this->config);
    }
}
