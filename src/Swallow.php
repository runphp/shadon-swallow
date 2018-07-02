<?php

declare(strict_types=1);

/*
 * This file is part of eelly package.
 *
 * (c) eelly.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swallow;

use Swallow\Core\Conf;
use Swallow\Core\Log;
use Swallow\Debug\Verify;

/**
 * Swallow 模块初始化.
 *
 * @author     SpiritTeam
 *
 * @since      2015年1月20日
 *
 * @version    1.0
 */
class Swallow
{
    /**
     * 目录.
     *
     * @var array
     */
    public static $path = ['app' => '', 'temp' => '', 'log' => ''];

    /**
     * @var \Phalcon\Di
     */
    public static $defaultDi;

    /**
     * 模块启动.
     *
     * @param array $path
     */
    public static function start($path): void
    {
        date_default_timezone_set('Asia/Shanghai');
        define('SWALLOW_PATH', __DIR__);
        self::$path = array_merge(self::$path, $path);
        // 初始化 DI
        self::$defaultDi = new \Swallow\Di\FactoryDefault();
        /*
        //初始化aop
        Aop\Instance::init(
            [
                'cacheDir' => self::$path['temp'].'/'.Conf::get('Swallow/inc/aop_cache_dir'),
                'aopAspect' => Conf::get('Swallow/inc/aop_aspect'), ]);
        //初始化Annotations
        Annotations\Instance::init(
            [
                'cacheKey' => Conf::get('Swallow/inc/annotation_cache_key'),
                'cacheTime' => Conf::get('Swallow/inc/annotation_cache_time'), ]);
        */
        //初始化验证类
        Verify::init(Conf::get('System/inc/MODULE_DEBUG'));
        //初始化Log
        Log::init([
                'is_debug'  => Conf::get('System/inc/MODULE_DEBUG'),
                'log_path'  => self::$path['log'],
                'is_whoops' => Conf::get('System/inc/IS_WHOOPS'),
        ]);
    }
}
