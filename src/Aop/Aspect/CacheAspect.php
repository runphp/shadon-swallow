<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Aop\Aspect;

use Swallow\Aop\Joinpoint;
use Swallow\Annotations\Annotation;
use Swallow\Core\Cache;
use Swallow\Exception\SystemException;

/**
 * 拦截器cache注解处理类
 *
 * @author     SpiritTeam
 * @since      2015年3月6日
 * @version    1.0
 */
class CacheAspect
{

    /**
     * 标签名
     * @var string
     */
    const TAG = 'cache';

    /**
     * 接入类型
     * @var string
     */
    const TYPE = 'around';

    /**
     * 处理缓存
     *
     * @param  Joinpoint $joinpoit
     */
    public static function run(Joinpoint $joinpoit)
    {
        $className = $joinpoit->getClassName();
        $set = Annotation::getInstance($className)->getMethod($joinpoit->getMethodName())
            ->getAttr(self::TAG);
        if (! is_array($set)) {
            throw new SystemException('缓存注解参数不正确！');
        }
        //处理预变量
        $key = $set['key'];
        if (false !== strpos($key, '$')) {
            $key = preg_replace_callback('/\$([A-Za-z0-9]+)/',
                function ($matchs) use($joinpoit) {
                    $val = $joinpoit->getArgs($matchs[1]);
                    $val = is_array($val) ? md5(var_export($val, true)) : $val;
                    return $val;
                }, $key);
        }
        //处理时间值
        $prefix = $set['time'];
        if (is_numeric($prefix)) {
            $prefix = intval($prefix);
            $model = '';
        } else {
            $model = $joinpoit->getClassPath()[0];
            $prefix = $model . '.' . $prefix;
        }
        $cache = Cache::getInstance($model);
        $data = $cache->get($key, $prefix);
        $args = $joinpoit->getArgs();
        $forceUpdate = isset($set['update']) && isset($args[$set['update']]) && true == $args[$set['update']];
        if (self::isClear() || empty($data) || $forceUpdate) {
            $joinpoit->process();
            $data = $joinpoit->getReturnValue();
            $cache->set($key, $data, $prefix);
        }
        $joinpoit->setReturnValue($data);
    }

    /**
     * 是否被强制清除
     *
     * @return boolean
     */
    private static function isClear()
    {
        static $r = null;
        if (isset($r)) {
            return $r;
        }
        if ((isset($_GET['clear']) && $_GET['clear'] == 'cache') && (DEBUG_MODE || $_ENV['isInternalUser'])) {
            $r = true;
        } else {
            $r = false;
        }
        return $r;
    }
}
