<?php
/*
 * PHP version 5.5
 *
 * @copyright Copyright (c) 2012-2017 EELLY Inc. (http://www.eelly.com)
 * @link      http://www.eelly.com
 * @license   衣联网版权所有
 */

namespace Swallow\Toolkit\Util;

/**
 * 时间操作函数.
 *
 * @author    SpiritTeam
 *
 * @since     2015年6月6日
 *
 * @version   1.0
 */
class Time
{
    /**
     * 格林威治时间转成Unix时间戳.
     *
     * @param string $gmtime
     *
     * @return string
     *
     * @author  Heyanwen<heyanwen@eelly.net>
     *
     * @since   2016-10-25
     */
    public static function gmtimeToTime($gmtime = null)
    {
        return isset($gmtime) ? $gmtime + 28800 : time();
    }

    /**
     * 获得当前格林威治时间的时间戳.
     *
     * @return number
     *
     * @author SpiritTeam
     *
     * @since  2015年6月6日
     */
    public static function gmtime($time = null)
    {
        $timezone = date('Z');
        $time = isset($time) ? $time : time();

        return $time - $timezone;
    }

    /**
     * 字符串转换为格林威治时间戳.
     *
     * @param string $time
     *
     * @return number
     *
     * @author SpiritTeam
     *
     * @since  2015年6月6日
     */
    public static function strtogmtime($time)
    {
        $time = strtotime($time);

        return self::gmtime($time);
    }

    /**
     * 判断时间段返回.
     *
     *
     * @param int $time
     *
     * @return number
     *
     * @author zengxiong<zengxiong@eelly.net>
     *
     * @since  2015-5-14
     */
    public static function timeSliceJudge($time)
    {
        $k = intval(date('d', time())) - intval(date('d', intval($time))); //计算两个时间的差
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
     * @param int $startDate yyyymmdd
     * @param int $endDate
     *
     * @return multitype:string
     *
     * @author  chenjinggui<chenjinggui@eelly.net>
     *
     * @since   2015年7月24日
     */
    public static function getDay($startDate, $endDate)
    {
        $dif_time = strtotime($endDate) - strtotime($startDate);
        $day_time = 24 * 60 * 60;
        $start_time = strtotime($startDate);
        $end_time = strtotime($endDate);
        $category = [];
        while ($start_time <= $end_time) {
            $category[] = intval(date('Ymd', $start_time));
            $start_time += $day_time;
        }

        return $category;
    }

    /**
     * 根据日期获取月份列表.
     *
     * @param int $startDate yyyymmdd
     * @param int $endDate
     *
     * @return array
     *
     * @author  zengzhihao
     *
     * @since   2014年11月21日
     */
    public static function getMonthByDay($startDate, $endDate)
    {
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);

        $startMonth = date('Ym', $startTime);
        $endMonth = date('Ym', $endTime);

        $startTime = strtotime($startMonth.'01');

        while ($startMonth <= $endMonth) {
            $date[] = intval($startMonth);
            //获取月份天数
            $day = date('t', $startTime);
            $startTime += $day * 3600 * 24;
            $startMonth = date('Ym', $startTime);
        }

        return $date;
    }

    /**
     * 获取当前时间戳的下几个12点.
     *
     * @param int $time 格林威治时间，比北京时间少8小时
     * @param int $days 天数
     *
     * @return int 格林威治时间
     *
     * @author 陈淡华<chendanhua@eelly.net>
     *
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
     * 获取当前时间戳若干个小时后的时间戳.
     *
     * @param int   $time      格林威治时间，比北京时间少8小时
     * @param int   $hour      小时数
     * @param array $countTime 在该时间段内才进行计时 is_gmtime时间段是否使用格林威治时间
     *
     * @return int 格林威治时间
     *
     * @author wuhao <wuhao@eelly.com>
     *
     * @since 2015-09-21
     */
    public static function getNextSeveralHours(
        $time,
        $hour,
        $countTime = ['start_time' => '9:00', 'end_time' => '21:00', 'is_gmtime' => false])
    {
        $time = intval($time);
        $hour = intval($hour);
        if ($time < 1 || $hour < 1) {
            return false;
        }
        if ($countTime['is_gmtime']) {
            $startTime = strtotime(date('Ymd ', $time).$countTime['start_time']);
            $endTime = strtotime(date('Ymd ', $time).$countTime['end_time']);
        } else {
            $startTime = strtotime(date('Ymd ', $time).$countTime['start_time']) - 28800;
            $endTime = strtotime(date('Ymd ', $time).$countTime['end_time']) - 28800;
        }
        $countSeconds = $endTime - $startTime;
        $day = 0;
        $remainSeconds = 0;
        if ($time < $startTime) {
            $day = floor($hour * 3600 / $countSeconds);
            $remainSeconds = ($hour * 3600) % $countSeconds;
        }
        if ($time >= $startTime && $time <= $endTime) {
            if ($endTime - $time >= $hour * 3600) {
                return $time + $hour * 3600;
            } else {
                $day = floor(($hour * 3600 - ($endTime - $time)) / $countSeconds) + 1;
                $remainSeconds = ($hour * 3600 - ($endTime - $time)) % $countSeconds;
            }
        }
        if ($time > $endTime) {
            $day = floor($hour * 3600 / $countSeconds) + 1;
            $remainSeconds = ($hour * 3600) % $countSeconds;
        }

        return $startTime + intval(86400 * $day) + $remainSeconds;
    }

    /**
     * 获取几天后凌晨00：00：00的时间戳.
     *
     * @param int  $unixtime 给定日期的时间戳
     * @param int  $day      天数
     * @param bool $isGmtime 判断$unixtime是否是格林威治时间的时间戳    【true：是 】 |【false：否】
     * @param boll $gmtime   返回时间戳格式       【true：格林威治时间 】 |【false：正常时区】
     *
     * @return number
     */
    public function toNextDayZeroClock($unixtime, $day = '1', $isGmtime = false, $gmtime = false)
    {
        $unixtime = intval($unixtime);
        if (!isset($unixtime) || $unixtime <= 0) {
            $unixtime = time();
        } else {
            if ($isGmtime) {
                $unixtime += date('Z');
            }
        }
        $next = strtotime(date('Y-m-d', $unixtime + 86400 * intval($day)));

        return $gmtime ? $next - date('Z') : $next;
    }

    /**
     * 获得当前格林威治时间的时间戳.
     *
     * @return number
     *
     * @author SpiritTeam
     *
     * @since  2015年6月6日
     * @deprecated
     */
    public static function eatime($time = null)
    {
        $time = isset($time) ? $time : time();
        return mongoDate(1000*$time);
    }

    /**
     * 获取本指定日期的月份第一天和最后一天.
     *
     * @param string $date 日期
     *
     * @return array
     *               move by zhangzeqiang 16/05/16
     */
    public static function getthemonth($date)
    {
        $firstday = date('Y-m-01', strtotime($date));
        $lastday = date('Y-m-d', strtotime("$firstday +1 month -1 day"));

        return [$firstday, $lastday];
    }

    /**
     * 时间戳转化与当前时间差.
     *
     * @return array
     *
     * @author zhongrongjie
     *
     * @since  2016年5月24日
     */
    public static function getDiffTime($date = null)
    {
        $date = !empty($date) ? $date : 0;
        $differ = self::gmtime() - $date;
        $type = 0;
        if ($differ < 0) {
            $differTime = '1秒';
            $type = 1;
        } elseif ($differ < 60) {
            $differTime = $differ.'秒';
            $type = 1;
        } elseif ($differ / 60 < 60) {
            $differTime = ceil($differ / 60).'分钟';
            $type = 2;
        } elseif ($differ / 3600 < 24) {
            $differTime = ceil($differ / 3600).'小时';
            $type = 3;
        } elseif ($differ / (24 * 3600) < 7) {
            $differTime = ceil($differ / (24 * 3600)).'天';
            $type = 4;
        } elseif ($differ / (7 * 24 * 3600) < 4) {
            $differTime = ceil($differ / (7 * 24 * 3600)).'周';
            $type = 5;
        } else {
            $differTime = '本月';
        }

        return ['differTime' => $differTime, 'differ' => $differ, 'type' => $type];
    }

    /**
     * 求货动态，获取列表时间的显示格式
     * • 当天发布的，显示今天
     * • 当天0点往前24小时以内的，显示昨天
     * • 当天0点往前24小时以外的，显示“日 月”（25 8月）.
     *
     * @return string
     *
     * @author zhangyingdi
     *
     * @since  2016年09月29日
     */
    public static function getListTime($date = null)
    {
        $startDate = !empty($date) ? strtotime(date('Y-m-d', $date)) : 0;
        $startDiffer = self::gmtime() - $startDate;
        $date = !empty($date) ? $date : 0;

        if ($startDiffer < 3600 * 24) {
            $differTime = '今天';
        } elseif ($startDiffer >= 3600 * 24 && $startDiffer < 3600 * 48) {
            $differTime = '昨天';
        } else {
            $differTime = date('d日 m月', $date);
        }

        return $differTime;
    }

    /**
     * 求货动态，详情页时间的显示格式
     * • 1分钟内，显示为  “刚刚 ”
     * • 当天时间，显示为“ 时：分”（13:08）
     * • 当天零点往前24小时内，显示为“昨天 时：分”（昨天 13:08）
     * • 当天零点往前24-48小时间，显示为“前天 时：分”（前天 13:08）
     * • 当天零点往前48小时间外，显示为“年/月/日 时：分”（16/9/20 13:08）.
     *
     * @return array
     *
     * @author zhangyingdi
     *
     * @since  2016年10月03日
     */
    public static function getDetailTime($date = null)
    {
        $nowTime = self::gmtime();
        $startDate = !empty($date) ? strtotime(date('Y-m-d', $date)) : 0;
        $startDiffer = $nowTime - $startDate;
        $date = !empty($date) ? $date : 0;

        if (($nowTime - $date) <= 60) {
            $differTime = '刚刚';
        } elseif ($startDiffer < 3600 * 24) {
            $differTime = date('H:i', $date);
        } elseif ($startDiffer > 3600 * 24 && $startDiffer <= 3600 * 48) {
            $differTime = '昨天 '.date('H:i', $date);
        } elseif ($startDiffer > 3600 * 48 && $startDiffer <= 3600 * 72) {
            $differTime = '前天 '.date('H:i', $date);
        } else {
            $differTime = date('y/m/d H:i', $date);
        }

        return $differTime;
    }
}
