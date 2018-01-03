<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Annotations;

use Swallow\Core;

/**
 * Annotation初始化 
 * 
 * @author     SpiritTeam
 * @since      2015年1月19日
 * @version    1.0
 */
class Instance
{

    /**
     * 初始化整个Aop
     * 
     * @param array $conf
     */
    public static function init(array $conf = array())
    {
        Cache::$cacheKey = isset($conf['cacheKey']) ? $conf['cacheKey'] : 'ANNOTATION_CACHE_DATA';
        Cache::$cacheTime = isset($conf['cacheTime']) ? $conf['cacheTime'] : 60;
        Cache::$cacheObj = isset($conf['cacheObj']) ? $conf['cacheObj'] : Core\Cache::getInstance();
        // 装载缓存读取保存
        register_shutdown_function('\Swallow\Annotations\Cache::save');
    }
}
