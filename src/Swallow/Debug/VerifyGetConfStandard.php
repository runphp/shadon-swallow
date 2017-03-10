<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Debug;

use Swallow\Debug\Trace;

/**
 * 验证是读取配置否跨模块读取
 *
 * @author     SpiritTeam
 * @since      2015年4月28日
 * @version    1.0
 */
class VerifyGetConfStandard
{

    /**
     * 验证是否跨模块调用
     *
     * @param string $source
     * @param string $class
     * @return string
     */
    public static function verify($source)
    {
        $filterArr = array('System', 'Swallow');
        $class = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 4);
        $class = isset(end($class)['class']) ? strstr(end($class)['class'], '\\', true) : '';
        if (! empty($class) && $source != $class && ! in_array($source, $filterArr) && ! in_array($class, $filterArr)) {
            Trace::dump($class . '模块不能跨模块调用' . $source . '模块的配置，请仔细阅读规则文档！');
        }
    }
}
