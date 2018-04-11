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
 * 数字扩展类
 *
 * @author    SpiritTeam
 * @since     2015年5月8日
 * @version   1.0
 */
class Number
{

    /**
     * 比较两个浮点数的大小
     *
     * @param float $leftValue
     * @param float $rightValue
     * @return number 1:>; 0:==; -1:<
     * 
     * @author 陈淡华<chendanhua@eelly.net>
     * @since  2015-5-8
     */
    public static function comparedNumber($leftValue, $rightValue)
    {
        $leftValue = floor(($leftValue + 0.00001) * 100);
        $rightValue = floor(($rightValue + 0.00001) * 100);
        if ($leftValue > $rightValue) {
            return 1;
        } elseif ($leftValue < $rightValue) {
            return -1;
        }
        return 0;
    }

    /**
     * 获取包含某个值的位运算组合(目前支持64以内)
     *
     * @param int $value 查询的值
     * @param int $level 字段所包含的个数
     * @return array
     *
     * @author 陈淡华<chendanhua@eelly.net>
     * @since  2015-8-9
     */
    public static function getGroupsByPosition($value, $level = 4) {
        $value = intval($value);
        
        $data = array();
        $level = pow(2, $level);
        for ($i=1; $i<$level; $i++) {
            $i & $value && $data[] = $i;
        }
        return $data;
    }
    
    /**
     * 获取某个值所包含的位运算组合(目前支持64以内)
     *
     * @param int $value 查询的值
     * @param int $level 字段所包含的个数
     * @return array
     *
     * @author 陈淡华<chendanhua@eelly.net>
     * @since  2015-8-9
     */
    public static function getChildrenByPosition($value, $level = 4) {
        $value = intval($value);
        
        $data = array();
        for ($i=0; $i<$level; $i++) {
            $j = pow(2, $i);
            $value & $j && $data[] = $j;
        }
        return $data;
    }
}