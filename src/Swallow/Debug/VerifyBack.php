<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Debug;

/**
 * 验证执行回溯规范
 *
 * @author     SpiritTeam
 * @since      2015年1月12日
 * @version    1.0
 */
class VerifyBack
{

    /**
     * 验证类的调用回溯
     *
     * @param string $className
     */
    public static function callClass($className)
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)[2];
        if (! empty($trace['class'])) {
            self::verify($className, $trace);
        }
    }

    /**
     * 检查调用
     *
     * @param string $className
     * @param array $trace
     */
    private static function verify($className, array $trace)
    {
        $classNameArr = explode('\\', $className); //被调用者
        $classFunction = "，类名：" . $trace['class'] . "，方法：" . $trace['function'];
        $msg = '';
        $classArr = explode('\\', $trace['class']); //在哪調用
        switch ($classNameArr[1]) {
            case 'Controller':
                break;
            case 'Logic':
                if ($classArr[0] == 'Swallow') {
                    break;
                }
                if (! in_array($classArr[1], ['Logic', 'Service'])) {
                    $msg = '逻辑层只能在服务层和逻辑层调用' . $classFunction;
                } elseif ($classArr[0] != $classNameArr[0]) {
                    $msg = '逻辑层不能跨模块调用逻辑层' . $classFunction;
                }
                break;
            case 'Service':
                if (! in_array($classArr[1], ['Logic', 'Controller', 'Console'])) {
                    $msg = '服务层只能在控制层和逻辑层调用' . $classFunction;
                } elseif ($classNameArr[0] == $classArr[0] && $classArr[1] == 'Logic') {
                    $msg = '逻辑层不能调用本模块的服务层' . $classFunction;
                }
                break;
            case 'Model':
                if ($classArr[1] !== 'Logic') {
                    $msg = '模型层只能在逻辑层调用' . $classFunction;
                } elseif ($classArr[0] != $classNameArr[0]) {
                    $msg = '模型层不能跨模块调用' . $classFunction;
                }
                break;
        }
        if (! empty($msg)) {
            throw new \Exception($msg);
        }
    }
}
