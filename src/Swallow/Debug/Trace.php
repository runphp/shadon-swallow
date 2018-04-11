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
 * 模块 -> 输出
 * 
 * @author     SpiritTeam
 * @since      2015年1月12日
 * @version    1.0
 */
class Trace
{

    /**
     * 输出并退出
     * 
     * @param mixed $data
     * @param boolean $pre
     */
    public static function dump($data, $pre = false)
    {
        if (! headers_sent()) {
            header("Content-type: text/html; charset=utf-8");
        }
        $pre && exit(nl2br(is_array($data) ? var_export($data, true) : $data));
        echo '<pre>';
        print_r($data);
        echo '</pre>';
        exit();
    }

    /**
     * 输出信息
     * 
     * @param mixed $data
     */
    public static function info($data)
    {
        echo nl2br(is_array($data) ? var_export($data, true) : $data);
    }
}