<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Traits;

/**
 * 单例trait
 * 
 * @author     SpiritTeam
 * @since      2015年1月13日
 * @version    1.0
 */
trait PublicObject
{

    /**
     * @var 模块
     */
    protected $module;
    
    /**
     * 合并配置
     *
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年11月12日
     */
    protected function getConfig()
    {
        $defaultDi = $this->getDI();
        $this->module = $module = strtolower(explode('\\', static::class)[0]);
        
        $configMaster = ! empty($module) ? 'config' . $module : 'config';
        if (! isset($defaultDi[$configMaster])) {
            $file = ROOT_PATH . '/application/' . $module . '/config/' . APPLICATION_ENV . '/config.php';
            $configModule = is_file($file) ? include $file : [];
            $config = $defaultDi->getConfig()->toArray();
            $defaultDi[$configMaster] = new \Swallow\Config\Config($config);
            $defaultDi[$configMaster]->mergeArray($configModule);
        }
        return $defaultDi[$configMaster];
    }

    /**
     * 获取缓存对象
     *
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年11月12日
     */
    protected function getCache()
    {
        $defaultDi = $this->getDI();
        $config = $this->getConfig()->toArray();
        $module = $this->module;
        
        $cacheMaster = 'cache';
        if (! isset($defaultDi[$cacheMaster . $module])) {
            $cache = ! empty($config['cache']) ? $config['cache'] : '';
            if (! empty($cache) && isset($cache['backend']) && isset($cache['frontend'])) {
                $cacheMaster .= $module;
                $defaultDi[$cacheMaster] = function () use($cache, $defaultDi)
                {
                    return $defaultDi['cacheManager']->getServer([], $cache);
                };
            }
        } else {
            $cacheMaster .= $module;
        }
        return $defaultDi[$cacheMaster];
    }
    
    /**
     * 获取Redis对象
     *
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年11月12日
     */
    protected function getRedis()
    {
        $defaultDi = $this->getDI();
        $config = $this->getConfig()->toArray();
        $module = $this->module;
    
        $redisMaster = 'redis';
        if (! isset($defaultDi[$redisMaster . $module])) {
            $redis = ! empty($config['redis']) ? $config['redis'] : '';
            if (! empty($redis)) {
                $redisMaster .= $module;
                $defaultDi[$redisMaster] = function () use($redis, $defaultDi)
                {
                    return $defaultDi['redisManager']->getServer([], $redis);
                };
            }
        } else {
            $redisMaster .= $module;
        }
        return $defaultDi[$redisMaster];
    }
}
