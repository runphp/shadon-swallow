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
    public static function callClass($className): void
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
    public static function callMethod(Joinpoint $jp): void
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
    private static function verify($className, array $trace): void
    {
        $classNameArr = explode('\\', $className);

        $classFunction = '类名：'.$trace['class'].'  方法：'.$trace['function'];
        $msg = '错误：不符合 App=>Service=>Logic=>Model App=>Controller=>Service=>Logic=>Model App=>Controller=>Service=>Logic=>Service=>Logic=>Model';
        $msg .= '调用规则，请仔细阅读规则文档！'.$classFunction;
        $msgOther = '错误提示：%s不能调用%s模块的%s，请仔细阅读规则文档！'.$classFunction;
        $classArr = explode('\\', $trace['class']);
        // 排除测试类
        if ('Test' == substr($trace['class'], -4) || 'TestCase' == substr($trace['class'], -8)) {
            return;
        }
        if ('App\Application\ServiceApplication' == $trace['class']) {
            return;
        }
        switch ($classNameArr[1]) {
            case 'Controller':
                if ('App' != substr($trace['class'], -3)) {
                    throw new CodeStyleException($msg);
                } elseif ($classNameArr[0] != $classArr[0] && !empty($classArr[1]) && 'Service' == $classArr[1]) {
                    throw new CodeStyleException(sprintf($msgOther, $classArr[1], '其他', $classNameArr[1]));
                }
                break;
            case 'Logic':
                // 忽略命令行app和统计app
                if (in_array($classArr[0], ['ConsoleApp', 'StatsApp'])) {
                    return;
                }
                if ($classNameArr[0] == $classArr[0] && in_array($classArr[1], ['Aspect', 'Logic', 'UnitTest'])) {
                    return;
                }
                if ('Service' != $classArr[1]) {
                    // 拦截器可以调用本模块逻辑层方法
                    if (in_array($classArr[1], ['Aspect', 'Extend']) && $classArr[0] == $classNameArr[0]) {
                        return;
                    }
                    if ('Swallow' == $classArr['0']) {
                        return;
                    }
                    throw new CodeStyleException($msg);
                } elseif ($classNameArr[0] != $classArr[0] && 'Service' == $classArr[1]) {
                    throw new CodeStyleException(sprintf($msgOther, $classArr[1], '其他', $classNameArr[1]));
                }
                break;
            case 'Model':
                if ('Swallow' == $classArr[0]) {
                    return;
                }
                if ('Logic' != $classArr[1]) {
                    throw new CodeStyleException($msg);
                } elseif ($classNameArr[0] != $classArr[0]) {
                    throw new CodeStyleException(sprintf($msgOther, '', '其他', $classNameArr[1]));
                }
                break;
            case 'Service':
                if (1 == count($classArr)) {
                    return;
                }
                if ('Logic' != $classArr[1]
                    && 'Controller' != substr($trace['class'], -10)
                    && 'Aspect' != substr($trace['class'], -6)
                    && 'Behavior' != $classArr[1]
                    && 'Aggregate' != $classArr[1]
                    && 'Phalcon' != $classArr[0]
                    && 'App' != $classArr[0]
                ) {
                    throw new CodeStyleException($msg);
                } elseif ($classNameArr[0] == $classArr[0] && 'Controller' != substr($trace['class'], -10) && 'Behavior' != substr($trace['class'], -8)) {
                    throw new CodeStyleException(sprintf($msgOther, $classNameArr[1], '本', $classNameArr[1]));
                }
                break;
        }
    }
}
