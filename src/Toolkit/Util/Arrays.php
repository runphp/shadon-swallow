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
     * 合并两个，或者多个数组
     *
     * 跟ArrayHelper::mergeArray的区别。
     *
     * ArrayHelper::mergeArray当索引是数字时不覆盖，这个方法是不同索引是不是数字，都进行覆盖
     *
     * @param array $a
     * @param array $b
     * @return array
     * @author zengzhihao<zengzhihao@eelly.net>
     * @since  2016年03月22日
     */
    public static function mergeArrayAll($a, $b) {
        $args = func_get_args();
        $res = array_shift($args);
        while (!empty($args)) {
            $next = array_shift($args);
            foreach ($next as $k => $v) {
                if (is_array($v) && isset($res[$k]) && is_array($res[$k]))
                    $res[$k] = self::mergeArray($res[$k], $v);
                else
                    $res[$k] = $v;
            }
        }
        return $res;
    }

    /**
     * 合并两个，或者多个数组
     *
     * @param array $a
     * @param array $b
     * @return array
     * @author zengzhihao<zengzhihao@eelly.net>
     * @since  2016年03月22日
     */
    public static function mergeArray($a, $b) {
        $args = func_get_args();
        $res = array_shift($args);
        while (!empty($args)) {
            $next = array_shift($args);
            foreach ($next as $k => $v) {
                if (is_integer($k))
                    isset($res[$k]) ? $res[] = $v : $res[$k] = $v;
                elseif (is_array($v) && isset($res[$k]) && is_array($res[$k]))
                    $res[$k] = self::mergeArray($res[$k], $v);
                else
                    $res[$k] = $v;
            }
        }
        return $res;
    }
    
    /**
     * 过滤数组中的键并将过滤的键映射成另外的键
     * @desc        注意，如果array中没有而filter有的键值将被忽略，也就是说最终输出结果中不含该键值，只能过滤第一层键值
     * @author      guoanqi
     * @date        2014/09/04 15:55:35
     *
     * @param array $array  要过滤的数组
     * @param array $filter 映射数组，键为要过滤的键，键对应的键值微映射键值，如：
     *                       $filter = array(
     *                          'a'=>'A',
     *                          'b'=>'B',
     *                          'c'=>'C'
     *                       )
     *                      则表示保留$array的a,b,c键值并改变键值为A,B,C返回
     * @param string $funcmap
     *
     * @return 过滤成功返回过滤后的数组，否则返回false
     * move by zhangzeqiang 2016/05/18
     */
    public static function arrayFilterMapKeys($array, $filter, $funcmap = null)
    {
        if(!is_array($array) || !is_array($filter))
        {
            return false;
        }
    
        // 函数过滤
        if(!empty($funcmap))
        {
            foreach($funcmap as $k=>$v)
            {
                if(isset($array[$k]) && function_exists($v))
                {
                    $array[$k] = $v($array[$k]);
                }
            }
        }
    
        $return = array_intersect_key($array, $filter);
        foreach($return as $k => $v)
        {
            $val = $return[$k];
            unset($return[$k]);
            $return[$filter[$k]] = $val;
        }
        return $return;
    }
    
    /**
     * 切换数组的下标
     * 
     * @desc    使用示例
     * 示例一：
     *  参数：
     *      $array      = array(
     *          0 => ['id' => 12, 'name' => 'a', 'age' => 12],
     *          1 => ['id' => 16, 'name' => 'b', 'age' => 13],
     *          2 => ['id' => 17, 'name' => 'b', 'age' => 14],
     *      ); 
     *      $field      = 'name'
     *      $multiple   = false
     *  结果：
     *      array(
     *          'a' => ['id' => 12, 'name' => 'a', 'age' => 12],
     *          'b' => ['id' => 17, 'name' => 'b', 'age' => 14],
     *      );
     *      
     * 示例二：
     *  参数：
     *      $array      = array(
     *          0 => ['id' => 12, 'name' => 'a', 'age' => 12],
     *          1 => ['id' => 16, 'name' => 'b', 'age' => 13],
     *          2 => ['id' => 17, 'name' => 'b', 'age' => 14],
     *      ); 
     *      $field      = 'name'
     *      $multiple   = false
     *  结果：
     *      $array = array(
     *          'a' => [['id' => 12, 'name' => 'a', 'age' => 12]],
     *          'b' => [['id' => 16, 'name' => 'b', 'age' => 13], ['id' => 17, 'name' => 'b', 'age' => 14]],
     *      );
     * 
     * @param   array   $array      需要操作的数组
     *                          
     * @param   string  $field      需要切换的字段 'name'
     * @param   string  $multiple   多维数组
     * @return  array
     *                          
     * 
     * @author  Heyanwen<heyanwen@eelly.net>
     * @since   2016-9-21
     */
    public static function switchArrayKey(array $array, $field, $multiple = false)
    {
        // 参数校验
        if (empty($array) || empty($field)){
            return array();
        }
        // 大数组分块处理
        $arrayChunk = array_chunk($array, 100);
        // 替换下标
        $result = array();
        $fields = is_array($field) ? $field : [$field];
        while ($chunk = current($arrayChunk)){
            foreach ($chunk as $k => $v){
                $keys   = [];
                foreach ($fields as $key){
                    $keys[] = !isset($v[$key]) ? '' : $v[$key];
                }
                $keys   = implode('_', $keys);
                $multiple ? $result[$keys][] = $v : $result[$keys] = $v;
            }
            next($arrayChunk);
        }
        
        return $result;
    }
    
    /**
     * 批量修改数组字段类型（递归）
     * 
     * @param   array   $array  数组
     * @param   integer $type   1.数字，2.字符串
     * @return  array
     * 
     * @author  Heyanwen<heyanwen@eelly.net>
     * @since   2016-10-5
     */
    public static function switchArrayType(array $array, $type)
    {
        // 参数校验
        if (empty($array) || !in_array($type, [1, 2])){
            return false;
        }
        
        // 类型转换
        $result = array();
        $method = $type == 1 ? 'intval' : 'strval';
        foreach ($array as $k => $v){
            $result[$k] = is_array($v) ? self::switchArrayType($v, $type) : $method($v);
        }
        
        return $result;
    }
    
    /**
     * 获取多维数组某个特定键的所有值
     *
     * @param  dataArr  array  数据数组
     * @param  keyStr   string 需要获取值得键
     * @author chenzhong
     */
    public static function getValueByKey(array $dataArr, $keyStr)
    {
        if (!trim($keyStr) || !is_array($dataArr))
        {
            return false;
        }
        preg_match_all("/\"$keyStr\";\w{1}:(?:\d+:|)(.*?);/", serialize($dataArr), $data);
        foreach ($data[1] as $k => $v)
        {
            if( $v !== '""' )
            {
                $res[] = str_replace('"',"",$v);
            }
        }
        return $res;
    }

    /**
     * 数组降维
     *@desc
     * 示例一
     * 参数： $arr = [
     *      [1],
     *      [2],
     *      [2],
     *      [3],
     * ]
     *      $unique = 0,
     * 结果：$result = [1, 2, 2, 3]
     * 
     * 示例二：
     * 参数： $arr = [
     *      [1],
     *      [2],
     *      [2],
     *      [3],
     * ]
     *      $unique = 1,
     * 结果：$result = [1, 2, 3]
     * 
     * @param array $arr  需处理的数组
     * @param int $unique 是否去重
     * @return array
     * @author wangjiang<wangjiang@eelly.net>
     * @since  2017年5月22日
     */
    public static function reduceDimension(array $arr, $unique = 1)
    {
        $result = [];
        foreach($arr as $val){
            if(is_array($val)){
                $result = array_merge($result, self::reduceDimension($val));
            }else{
                $result[] = $val;
            }
        }
        return 1 === $unique ? array_unique($result) : $result;
    }
}