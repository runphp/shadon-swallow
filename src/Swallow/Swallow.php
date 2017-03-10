<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow;

use Swallow\Aop;
use Swallow\Annotations;
use Swallow\Core\Conf;
use Swallow\Debug\Verify;
use Swallow\Core\Log;
use Swallow\Core\Rpc;

/**
 * Swallow 模块初始化
 *
 * @author     SpiritTeam
 * @since      2015年1月20日
 * @version    1.0
 */
class Swallow
{

    /**
     * 目录
     * @var array
     */
    public static $path = array('app' => '', 'temp' => '', 'log' => '');

    /**
     * 模块启动
     *
     * @param array $path
     */
    public static function start($path)
    {
        define('SWALLOW_PATH', __DIR__);
        self::$path = array_merge(self::$path, $path);
        //初始化aop
        Aop\Instance::init(
            array(
                'cacheDir' => self::$path['temp'] . '/' . Conf::get('Swallow/inc/aop_cache_dir'),
                'aopAspect' => Conf::get('Swallow/inc/aop_aspect')));
        //初始化Annotations
        Annotations\Instance::init(
            array(
                'cacheKey' => Conf::get('Swallow/inc/annotation_cache_key'),
                'cacheTime' => Conf::get('Swallow/inc/annotation_cache_time')));
        //初始远程化
        Rpc::init(
            array(
                'type' => Conf::get('Swallow/inc/rpc_type'),
                'cacheDir' => self::$path['temp'] . '/' . Conf::get('Swallow/inc/rpc_cache_dir'),
                'module' => Conf::get('Swallow/inc/rpc_module'),
                'setting' => array('manage' => Conf::get('System/inc/SOA_MANAGE_SERVER'))));
        //初始化验证类
        Verify::init(Conf::get('System/inc/MODULE_DEBUG'));
        //初始化Log
        Log::init(
            array(
                'is_debug' => Conf::get('System/inc/MODULE_DEBUG'),
                'log_path' => self::$path['log'],
                'is_whoops' => Conf::get('System/inc/IS_WHOOPS')));
    }

    /**
     * 自动加载
     *
     * @param string $className
     */
    public static function autoload($className)
    {
        return;
        $class = explode('\\', $className);
        if ('Swallow' == $class[0]) {
            unset($class[0]);
            return SWALLOW_PATH . '/' . implode('/', $class) . '.php';
        } elseif ($class[1] == 'Service' && Rpc::isRpcModule($class[0])) {
            return Rpc::loadClass($className);
        } else {
            if (! in_array($class[1], array('Logic', 'Model', 'Service', 'Controller', 'Extend', 'Exception', 'Aspect', 'Init'))) {
                return false;
            }
            return self::$path['app'] . '/' . implode('/', $class) . '.php';
        }
    }
}