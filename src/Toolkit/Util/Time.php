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
 * 时间操作函数
 * 
 * @author    SpiritTeam
 * @since     2015年6月6日
 * @version   1.0
 */
class Time
{

    /**
     * 获得当前格林威治时间的时间戳 
     * 
     * @return number
     * @author SpiritTeam
     * @since  2015年6月6日
     */
    public static function gmtime($time = null)
    {
        $timezone = date('Z');
        $time = isset($time) ? $time : time();
        return $time - $timezone;
    }

    /**
     * 字符串转换为格林威治时间戳 
     * 
     * @param  string $time
     * @return number
     * @author SpiritTeam
     * @since  2015年6月6日
     */
    public static function strtogmtime($time)
    {
        $time = strtotime($time);
        return self::gmtime($time);
    }
    
    /**
     * 判断时间段返回
     *
     *
     * @param int $time
     * @return number
     * @author zengxiong<zengxiong@eelly.net>
     * @since  2015-5-14
     */
    public static function timeSliceJudge($time)
    {
        $k = intval(date("d", time())) - intval(date('d', intval($time))); //计算两个时间的差
        if ($k == 0) { //一天之内
            $t = $k;
        } else {
            $t = round((time() - $time) / 3600 / 24);
        }
        return $t;
    }
    
    /**
     * 获取两个日期间的所有日期
     * 
     * @param   int   $startDate  yyyymmdd
     * @param   int   $endDate
     * @return  multitype:string
     * @author  chenjinggui<chenjinggui@eelly.net>
     * @since   2015年7月24日
     */
    public static function getDay($startDate, $endDate)
    {
        $dif_time = strtotime($endDate) - strtotime($startDate);
        $day_time = 24 * 60 * 60;
        $start_time = strtotime($startDate);
        $end_time = strtotime($endDate);
        $category = array();
        while ($start_time <= $end_time) {
            $category[] = intval(date('Ymd', $start_time));
            $start_time += $day_time;
        }
        return $category;
    }
    
    /**
     * 根据日期获取月份列表
     * 
     * @param   int   $startDate  yyyymmdd
     * @param   int   $endDate
     * @return  array
     * @author  zengzhihao
     * @since   2014年11月21日
     */
    public static function getMonthByDay($startDate, $endDate)
    {
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);
        
        $startMonth = date('Ym', $startTime);
        $endMonth = date('Ym', $endTime);
        $date = [];
        while ($startMonth <= $endMonth) {
            $date[] = intval($startMonth);
            $startTime = strtotime('+1 month', $startTime);
            $startMonth = date('Ym', $startTime);
        }
        
        return $date;
    }

    /**
     * 获取当前时间戳的下几个12点
     *
     * @param int $time 格林威治时间，比北京时间少8小时
     * @param int $days 天数
     * @return int 格林威治时间
     * 
     * @author 陈淡华<chendanhua@eelly.net>
     * @since  2015-8-4
     */
    public static function getDaysLaterNoonTime($time, $days = 1)
    {
        $time = intval($time);
        $days = intval($days);
        if ($time < 1 || $days < 1) {
            return false;
        }
        
        // 转成北京时间
        $time += 28800;
        // 判断当前时间戳是否是早上
        if (date('a', $time) == 'am') {
            $time -= 86400;
        }
        
        // 返回n天后的中午12点
        return strtotime(date('Y-m-d 12:00:00', $time)) + 86400 * $days - 28800;
    }

    /**
     * 获取系统时间戳
     *
     * @param int $type 1微秒
     * @author wangjiang<wangjiang@eelly.net>
     * @since  2017-03-10
     */
    public static function getSystemTime($type=null)
    {
        return 1 == $type ? microtime(true) : time();
    }
}
