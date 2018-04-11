<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Toolkit\Util;

/**
 * 字符串扩展类
 *
 * @author    SpiritTeam
 * @since     2015年5月8日
 * @version   1.0
 */
class Strings
{

    /**
     * 生成随机字符串
     *
     * @param  number $len       需要随机字符串长度
     * @param  number $type      组合需要的字符串
     *                           0001：大写字母，0010：小写字母，0100：数字，1000：特殊字符；根据需要组合(二进制相加后对应的十进制值)
     *                           默认：7=0111，即：大写字母+小写字母+数字
     * @param  string $addChars  添加自己的字符串
     * @return string
     *
     * @author SpiritTeam
     * @since  2015-6-2
     */
    public static function randString($len = 10, $type = 7, $addChars = '')
    {
        static $strings = array(
            1 => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            2 => 'abcdefghijklmnopqrstuvwxyz',
            4 => '0123456789',
            8 => '!@#$%^&*()-_ []{}<>~`+=,.;:/?|');
        $type > 15 && $type = 15;
        $chars = $addChars;
        foreach ($strings as $k => $v) {
            $type & $k && $chars .= $v;
        }
        return substr(str_shuffle($chars), 0, $len);
    }
    
    /**
     * Utf-8 gb2312都支持的汉字截取函数 cutStr(字符串, 截取长度, 开始长度, 编码)
     *
     * @param $string string 要处理的字符串
     * @param $sublen number 截取长度
     * @param $start  number 偏移量
     * @param $code   string 要处理的字符串编码
     * @return string
     */
    public static function cutStr($string, $sublen, $start = 0, $code = 'UTF-8')
    {
        if ($code == 'UTF-8') {
            $t_string = [];
            $pa = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/";
            preg_match_all($pa, $string, $t_string);
    
            if (count($t_string[0]) - $start > $sublen)
                return join('', array_slice($t_string[0], $start, $sublen)) . "...";
            return join('', array_slice($t_string[0], $start, $sublen));
        } else {
            $start = $start * 2;
            $sublen = $sublen * 2;
            $strlen = strlen($string);
            $tmpstr = '';
    
            for ($i = 0; $i < $strlen; $i ++) {
                if ($i >= $start && $i < ($start + $sublen)) {
                    $tmpstr = ord(substr($string, $i, 1)) > 129 ? $tmpstr . substr($string, $i, 2) : $tmpstr . substr($string, $i, 1);
                }
                if (ord(substr($string, $i, 1)) > 129)
                    $i ++;
            }
    
            if (strlen($tmpstr) < $strlen)
                $tmpstr .= "...";
            return $tmpstr;
        }
    }
}