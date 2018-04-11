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
 * 数组扩展类
 * 
 * @author    SpiritTeam
 * @since     2015年5月8日
 * @version   1.0
 */
class Arrays
{

    /**
     * 二维数组 排序
     * Arrays::multisort($data, 'order', SORT_ASC, 'id', SORT_DESC);
     * 
     * @param  mixed ...$args
     * @return array
     * @author SpiritTeam
     * @since  2015年6月6日
     */
    public static function multisort()
    {
        $args = func_get_args();
        $data = array_shift($args);
        foreach ($args as $n => $field) {
            if (is_string($field)) {
                $tmp = array();
                foreach ($data as $key => $row)
                    $tmp[$key] = $row[$field];
                $args[$n] = $tmp;
            }
        }
        $args[] = &$data;
        call_user_func_array('array_multisort', $args);
        return $data;
    }

    /**
     * 读取一列
     * 
     * @param  array  $array      源数组
     * @param  string $column_key 列名
     * @param  string $index_key  是否使用某个列做键值
     * @return array
     * @author SpiritTeam
     * @since  2015年6月6日
     */
    public static function column(array $array, $column_key, $index_key = null)
    {
        if (function_exists('array_column')) {
            return array_column($array, $column_key, $index_key);
        }
        if (is_null($column_key)) {
            return $array;
        }
        $result = [];
        foreach ($array as $arr) {
            if (! is_array($arr)) {
                continue;
            }
            $value = $arr[$column_key];
            if (isset($index_key)) {
                $result[$arr[$index_key]] = $value;
            } else {
                $result[] = $value;
            }
        }
        return $result;
    }

    /**
     * 数组字段映射
     * Arrays::mapping(array('id'=>11),array('id'=>'uid'));
     * 将id的键名改为uid
     *
     * @param  array $array
     * @param  array $map
     * @return array
     * @author SpiritTeam
     * @since  2015年6月6日
     */
    public static function mapping(array $array, array $map)
    {
        $retval = array();
        if (empty($map)) {
            return $retval;
        }
        foreach ($array as $key => $value) {
            $retval[isset($map[$key]) ? $map[$key] : $key] = $value;
        }
        return $retval;
    }

    /**
     * 获取数组最后的一个键值
     *
     *
     * @param array $arr
     * @return array
     * @author zengxiong<zengxiong@eelly.net>
     * @since  2015年5月18日
     */
    public static function getArrayLastKey(array $arr)
    {
        $keys = array_keys($arr);
        return end($keys);
    }

    /**
     * 多维数组转一维数组,此方法不保留键名
     *
     * @param  $arr     array    要处理的多维数组
     * @param  $fields  array    要保留字段，默认全部保留
     * @return array
     * @author SpiritTeam
     * @since  2015年10月12日
     */
    public static function multidimenToOne(array $arr, array $fields = [])
    {
        if (! is_array($arr))
            return false;
        
        $res = [];
        foreach ($arr as $key => $value) {
            if (is_array($value)) {
                $tmp = Arrays::multidimenToOne($value, $fields);
                $res = array_merge($res, $tmp);
                continue;
            }
            if (empty($fields) || in_array($key, $fields)) {
                array_push($res, $value);
            }
        }
        return $res;
    }

    /**
     * 数组键值转换字符串
     *
     * @param $data
     * @author 范世军<fanshijun@eelly.net>
     * @since  Dec 7, 2015
     */
    public static function toString($data)
    {
        if (empty($data) && !is_object($data)) {
            return [];
        }
        if (is_array($data)) {
            foreach ($data as $key => $val) {
                if (is_array($val)) {
                    $data[$key] = self::toString($val);
                } else {
                    !is_bool($val) && !is_object($data) && $data[$key] = (string) $val;
                }
            }
        } else if (!is_bool($data) && !is_object($data)) {
            $data = (string) $data;
        }
        return $data;
    }
}