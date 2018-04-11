<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Aop;

/**
 * Aop初始化
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
        Loader::$cacheDir = isset($conf['cacheDir']) ? $conf['cacheDir'] : \Swallow\Swallow::$path['temp'] . '/' . 'aopcache';
        if (! empty($conf['aopAspect'])) {
            foreach ($conf['aopAspect'] as $value) {
                Loader::$aspect[] = '\\Swallow\\Aop\\Aspect\\' . ucfirst($value) . 'Aspect';
            }
        }
    }
}
