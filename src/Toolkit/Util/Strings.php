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
    
    /**
     * 字符串长度
     * @param   string  $string
     * @param   bool    $type       str: 字符长度；char: 字长度，中文英文都算1个字；word: 字长度2，中文算2个，英文算1个
     * @param   bool    $onlyWord   只算字，中文算一个，英文算半个
     *
     * @return  type
     */
    public static function sysStrlen($string, $type = 'str') {
        $rslen  = 0;
        $strlen = 0;
        $length = strlen($string);
        switch ($type) {
            case 'str':
                $rslen = $length;
                break;
            case 'char':
                while ($strlen < $length) {
                    $stringTMP = substr($string, $strlen, 1);
                    if (ord($stringTMP) >= 224) {
                        $strlen = $strlen + 3;
                    } elseif (ord($stringTMP) >= 192) {
                        $strlen = $strlen + 2;
                    } else {
                        $strlen = $strlen + 1;
                    }
                    $rslen++;
                }
                break;
            case 'word':
                while ($strlen < $length) {
                    $stringTMP = substr($string, $strlen, 1);
                    if (ord($stringTMP) >= 224) {
                        $strlen = $strlen + 3;
                        $rslen += 2;
                    } elseif (ord($stringTMP) >= 192) {
                        $strlen = $strlen + 2;
                        $rslen += 2;
                    } else {
                        $strlen = $strlen + 1;
                        $rslen++;
                    }
                }
                break;
        }
    
        return $rslen;
    }
    
    /**
     * 字符串截取，支持中文和其他编码
     *
     * @param string $str 需要转换的字符串
     * @param string $start 开始位置
     * @param string $length 截取长度
     * @param string $charset 编码格式
     * @param string $suffix 截断显示字符
     * @return string
     */
    static public function msubstr($str, $start=0, $length, $charset="utf-8") {
        if(function_exists("mb_substr"))
            $slice = mb_substr($str, $start, $length, $charset);
        elseif(function_exists('iconv_substr')) {
            $slice = iconv_substr($str,$start,$length,$charset);
        }else{
            $re['utf-8']   = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
            $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
            $re['gbk']    = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
            $re['big5']   = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
            preg_match_all($re[$charset], $str, $match);
            $slice = join("",array_slice($match[0], $start, $length));
        }
        return $slice;
    }
    
    /**
     * 超出字符串替换
     * 
     * @param string $str 需要处理的字符串
     * @param int $len 字符串长度限定
     * @param string $replaceStr 超长部分替换为此字符串
     * @return string
     * @author Xuxiao<xuxiao@eelly.net>
     * @since  2016年7月5日
     */
    static function replaceStrOverLength($str, $len, $replaceStr='...')
    {
        $strlen = Strings::sysStrlen($str, 'char');
        return ($strlen <= $len) ? $str : (Strings::msubstr($str, 0, $len) . $replaceStr);
    }
}