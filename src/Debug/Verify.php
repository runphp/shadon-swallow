<?php
/*
 * PHP version 5.5
 *
 * @copyright Copyright (c) 2012-2017 EELLY Inc. (http://www.eelly.com)
 * @link      http://www.eelly.com
 * @license   衣联网版权所有
 */

namespace Swallow\Debug;

use Swallow\Aop\Joinpoint;

/**
 * 验证类.
 * 
 * @author     SpiritTeam
 *
 * @since      2015年1月12日
 *
 * @version    1.0
 */
class Verify
{
    /**
     * 绑定验证
     * 
     * @param bool $status
     */
    public static function init($status)
    {
        self::debug($status);
        if (self::debug()) {
            VerifyTableConfStandard::verify();
        }
    }

    /**
     * 引用类时的校验.
     * 
     * @param string $className
     */
    public static function callClass($className)
    {
        static $calledClass = [];
        if (self::debug()) {
            if (!isset($calledClass[$className])) {
                VerifyCodeStandard::verify($className);
                $calledClass[$className] = true;
            }
            VerifyBackTraceStandard::callClass($className);
        }
    }

    /**
     * 绑定调用函数的校验.
     * 
     * @param Joinpoint $jp
     */
    public static function callMethod(Joinpoint $jp)
    {
        if (self::debug() && $jp->getMethodName() != 'init') {
            VerifyBackTraceStandard::callMethod($jp);
        }
    }

    /**
     * 验证sql.
     * 
     * @param string $sql
     */
    public static function querySql($sql)
    {
        if (self::debug()) {
            VerifySqlStandard::verify($sql);
        }
    }

    /**
     * 验证是读取配置否跨模块读取.
     *
     * @param string $source
     */
    public static function getConf($source)
    {
        if (self::debug()) {
            VerifyGetConfStandard::verify($source);
        }
    }

    /**
     * 获取debug状态 
     * 
     * @param bool $setting
     *
     * @return bool
     */
    public static function debug($setting = null)
    {
        static $status = true;
        if (is_bool($setting)) {
            $status = $setting;
        }

        return $status;
    }
}
