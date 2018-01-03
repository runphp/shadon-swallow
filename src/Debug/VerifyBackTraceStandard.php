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
use Swallow\Exception\CodeStyleException;

/**
 * 验证执行回溯规范.
 *
 * @author     SpiritTeam
 *
 * @since      2015年1月12日
 *
 * @version    1.0
 */
class VerifyBackTraceStandard
{
    /**
     * 验证类的调用回溯.
     *
     * @param string $className
     */
    public static function callClass($className)
    {
        $traceArr = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        if (!isset($traceArr[3])) {
            return;
        } else {
            $trace = $traceArr[3];
        }
        if (!empty($trace['class'])) {
            self::verify($className, $trace);
        }
    }

    /**
     * 验证方法的调用回溯.
     *
     * @param Joinpoint $jp
     */
    public static function callMethod(Joinpoint $jp)
    {
        $traceArr = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        if (!isset($traceArr[4])) {
            return;
        } else {
            $trace = $traceArr[4];
        }
        if (!empty($trace['class']) && $jp->getClassName() != $trace['class']) {
            self::verify($jp->getClassName(), $trace);
        }
    }

    /**
     * 检查调用.
     *
     * @param string $className
     * @param array  $trace
     */
    private static function verify($className, array $trace)
    {
        $classNameArr = explode('\\', $className);

        $classFunction = "类名：".$trace['class']."  方法：".$trace['function'];
        $msg = "错误：不符合 App=>Service=>Logic=>Model App=>Controller=>Service=>Logic=>Model App=>Controller=>Service=>Logic=>Service=>Logic=>Model";
        $msg .= '调用规则，请仔细阅读规则文档！'.$classFunction;
        $msgOther = '错误提示：%s不能调用%s模块的%s，请仔细阅读规则文档！'.$classFunction;
        $classArr = explode('\\', $trace['class']);
        // 排除测试类
        if (substr($trace['class'], -4) == 'Test' || 'TestCase' == substr($trace['class'], -8)) {
            return;
        }
        switch ($classNameArr[1]) {
            case 'Controller':
                if (substr($trace['class'], -3) != 'App') {
                    throw new CodeStyleException($msg);
                } elseif ($classNameArr[0] != $classArr[0] && !empty($classArr[1]) && $classArr[1] == 'Service') {
                    throw new CodeStyleException(sprintf($msgOther, $classArr[1], '其他', $classNameArr[1]));
                }
                break;
            case 'Logic':
                // 忽略命令行app和统计app
                if (in_array($classArr[0], ['ConsoleApp', 'StatsApp', 'ServiceApp'])) {
                    return;
                }
                if ($classNameArr[0] == $classArr[0] && in_array($classArr[1], ['Aspect', 'Logic', 'UnitTest'])) {
                    return;
                }
                if ($classArr[1] != 'Service') {
                    // 拦截器可以调用本模块逻辑层方法
                    if (in_array($classArr[1], ['Aspect', 'Extend']) && $classArr[0] == $classNameArr[0]) {
                        return;
                    }
                    if ('Swallow' == $classArr['0']) {
                        return;
                    }
                    throw new CodeStyleException($msg);
                } elseif ($classNameArr[0] != $classArr[0] && $classArr[1] == 'Service') {
                    throw new CodeStyleException(sprintf($msgOther, $classArr[1], '其他', $classNameArr[1]));
                }
                break;
            case 'Model':
                if ($classArr[0] == 'Swallow') {
                    return;
                }
                if ($classArr[1] != 'Logic') {
                    throw new CodeStyleException($msg);
                } elseif ($classNameArr[0] != $classArr[0]) {
                    throw new CodeStyleException(sprintf($msgOther, '', '其他', $classNameArr[1]));
                }
                break;
            case 'Service':
                if (count($classArr) == 1) {
                    return;
                }
                if ($classArr[1] != 'Logic'
                    && substr($trace['class'], -10) != 'Controller'
                    && substr($trace['class'], -6) != 'Aspect'
                    && $classArr[1] != 'Behavior'
                    && $classArr[1] != 'Aggregate'
                    && $classArr[0] != 'Phalcon'
                ) {
                    throw new CodeStyleException($msg);
                } elseif ($classNameArr[0] == $classArr[0] && substr($trace['class'], -10) != 'Controller' && substr($trace['class'], -8) != 'Behavior') {
                    throw new CodeStyleException(sprintf($msgOther, $classNameArr[1], '本', $classNameArr[1]));
                }
                break;
        }
    }
}
