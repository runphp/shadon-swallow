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
                $rslen = $strlen;
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
     * 字符串按字节截取，支持中文和其他编码
     * 
     * @param string $string 需要转换的字符串
     * @param string $start 开始位置
     * @param string $length 截取长度
     * @param string $charset 编码格式
     * @param string $suffix 截断显示字符
     * @return string
     * @author 曾雄<zengxiong@eelly.net>
     * @since  2016-1-18
     */
    static public function getStringByChar($string, $start = 0, $length = 10, $charset = "utf-8", $suffix = false)
    {
        $re = [
            'utf-8' => "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/", 
            'gb2312' => "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/", 
            'gbk' => "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/", 
            'big5' => "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/"];
        preg_match_all($re[$charset], $string, $match);
        
        $tmpLength = 0;
        $retval = '';
        if (! empty($match[0]) && is_array($match[0])) {
            foreach ($match[0] as $k => $v) {
                $tmpLength += strlen($v);
                if ($tmpLength <= $start) {
                    continue;
                }
                if ($tmpLength > ($start + $length)) {
                    break;
                }
                $retval .= $v;
            }
        }
        return $suffix ? $retval . '...' : $retval;
    }

    /**
     * 屏蔽手机的中间端
     *
     *
     * @param string $tel
     * @param int $length 前后保留位数
     * @return string
     * @author zhangzeqiang<zhangzeqiang@eelly.net>
     * @since  2016年3月31日
     */
    public static function changeTel($tel, $length = 3)
    {
        $headTel = substr($tel, 0, $length);
        $num = strlen($tel);
        $add = '*';
        $num > 2 && ($num - 6 > 0) && $add .= str_repeat('*',$num - 6).substr($tel, -$length, $length);
        return $headTel . $add;
    }
    /**
     * 屏蔽姓名的中间端
     * 
     * 西门     ====》西*
     * 西门吹   ====》西*吹
     * 西门吹水  ====》西**水
     * 
     * @param string $name
     * @param string $charset
     * @param number $num
     * @return string
     * @author崔展铭<cuizhanming@eelly.net>
     * @since  2016年3月29日
     */
    static public function changeName($name, $charset = "utf-8", $wantStars = 0)
    {
        $realName = self::msubstr($name, 0, 1, $charset);
        $num = self::sysStrlen($name, 'char');
        $add = '*';
        if ($wantStars > 0){
            $add .= str_repeat('*',$wantStars-1).self::msubstr($name, $num - 1, 1, $charset);
        }else {
            $num > 2 && $add .= str_repeat('*',$num-3).self::msubstr($name, $num - 1, 1, $charset);
        }
        return $realName . $add;
    }
    

    /**
     * scws分词方法
     *
     * @param string $string
     * @param int $num 至少要取的分词个数
     * @param array $config
     *      charset 字符集
     *      duality 设定是否将闲散文字自动以二字分词法聚合
     *      ignore 设定分词返回结果时是否去除一些特殊的标点符号之类
     *      attr 表示返回的词性必须在列表~表示取反
     * @return array
     * @author wuhao <wuhao@eelly.net>
     * @since 2016-04-09
     */
    public static function scwsWordSegment($string, $num = 10, $config = ['charset' => 'utf8', 'duality' => 'yes', 'ignore' => 'yes', 'attr' => '~en'])
    {
        if (empty($string)) {
            return [];
        }
        if (function_exists('scws_new')) {
            $cws = scws_new();
            $cws->set_charset($config['charset']);
            $cws->set_duality($config['duality']);
            $cws->set_ignore($config['ignore']);
            $cws->send_text($string);
            return $cws->get_tops($num, $config['attr']);
        } else {
            return [];
        }
    }

    /**
     * 数据压缩用于进数据库
     * 
     * @param mixed $dataToStore string, array, object, etc.
     * @param int $strlen 截断长度
     * @return string
     * @author wuhao <wuhao@eelly.net>
     * @since 2016-05-03
     */
    public static function gz($dataToStore, $strlen = 60000)
    {
        if (is_string($dataToStore)) {
            $dataToStore = sub_str($dataToStore, $strlen);
        }
        $strData = strtr(base64_encode(addslashes(gzcompress(serialize($dataToStore), 9))), '+/=', '-_,');
        return $strData;
    }

    /**
     * 过滤位置信息
     *    过滤 尾部“省”，“市”，“区”
     *    如：
     *    广东省      广东
     *    广州市      广州
     *    天河区      天河
     *
     * @param string $str
     * @author Xuxiao<xuxiao@eelly.net>
     * @since  2016年6月15日
     */
    public static function filterLocationChars($str)
    {
        // 定义需要过滤的字符
        $filterChars = "省|市|区";
        mb_regex_encoding('utf-8');
        // 如果最后一个字符是需要被过滤的字符，则执行过滤
        return ($str && mb_strpos($filterChars, mb_substr($str, - 1, 1, 'utf-8'), 0, 'utf-8') !== false) ? mb_ereg_replace($filterChars, '', $str) : $str;
    }
    
    /**
     * 数组转换, 驼峰命名法转下划线风格
     * 
     * @param array $data
     * @see Strings::toUnderScore
     */
    public static function toUnderScoreArray($data) 
    {
        if (is_string($data)){
            return $data;
       }

        if (is_array($data)){
            foreach ($data as $key => $value){
                $newvalue = static::toUnderScoreArray($value);
                unset($data[$key]);
                $newkey = static::toUnderScore($key);
                $data[$newkey]=$newvalue;
            }
        }

         return $data;
    }
    
    /**
     * 驼峰命名法转下划线风格
     * 
     * @param string $str
     * @return string
     * 
     * @author lizhouhuan<lizhuohuan@eelly.net>
     * @since 2017年2月20日
     */
    public static function toUnderScore($str)
    {
        
        $array = array();
        for($i=0;$i<strlen($str);$i++){
            if($str[$i] == strtolower($str[$i])){
                $array[] = $str[$i];
            }else{
                if($i>0){
                    $array[] = '_';
                }
                $array[] = strtolower($str[$i]);
            }
        }
        
        $result = implode('',$array);
        return $result;
    }
    
    /**
     * 数组转换, 下划线风格转驼峰命名法
     * 
     * @param array $data
     * @see Strings::toUnderScore
     */
    public static function toCamelCaseArray($data) 
    {
        if (is_string($data)){
            return $data;
       }

        if (is_array($data)){
            foreach ($data as $key => $value){
                $newvalue = static::toCamelCaseArray($value);
                unset($data[$key]);
                $newkey = static::toCamelCase($key);
                $data[$newkey]=$newvalue;
            }
        }

         return $data;
    }
    
    /**
     * 下划线风格转驼峰命名法
     * 
     * @param string $str
     * @return string
     * 
     * @author lizhouhuan<lizhuohuan@eelly.net>
     * @since 2017年2月20日
     */
    public static function toCamelCase($str)
    {
        $array = explode('_', $str);
        $result = '';
        if (count($array) == 1) {
            $result = $array[0];
        } else {
            foreach($array as $key => $value){
                $result .= $key == 0 ? $value : ucfirst($value);
            }
        }
        
        return $result;
    }
    
    /**
     * 计算两组经纬度坐标 之间的距离（数据库储存是先经纬度，app传来的数据是经纬度 ，调用搜索接口的是纬经度  搜索返回的是纬经度）
     *
     * @params  $map1 数据库数据（纬度，经度）
     * @params  $map2 传送数据（纬度，经度）
     * @params  len_type （1:m or 2:km);
     * @return m or km
     * @author sujinyue
     * @date 2014-05-14
     */
    public static function getDistance($map1, $map2, $len_type = 1, $decimal = 2)
    {
    
        if( empty($map1) || empty($map2))
        {
            return '';
        }
        $mapArr1 = explode(',', $map1);
        $lat1 = $mapArr1['0']; //纬度
        $lng1 = $mapArr1['1']; //经度
        $mapArr2 = explode(',', $map2);
        $lat2 = $mapArr2['0']; //纬度
        $lng2 = $mapArr2['1']; //经度
        $EARTH_RADIUS=6378.137;
        $PI=3.1415926;
        $radLat1 = $lat1 * $PI / 180.0;
        $radLat2 = $lat2 * $PI / 180.0;
        $a = $radLat1 - $radLat2;
        $b = ($lng1 * $PI / 180.0) - ($lng2 * $PI / 180.0);
        $s = 2 * asin(sqrt(pow(sin($a/2),2) + cos($radLat1) * cos($radLat2) * pow(sin($b/2),2)));
        $s = $s * $EARTH_RADIUS;
        $s = round($s * 1000);
        if ($len_type > 1)
        {
            $s /= 1000;
            $return_data = round($s,$decimal).'km';
        }
        else
        {
            $return_data = round($s,$decimal).'m';
        }
        return $return_data;
    }
    
    /**
     * 将商场带下划线的下标转为大写模式，用于APP接口中
     *
     * @param  array $arr      商场数组
     * @param  array $localKey 自定义部分下标改为指定下标，如：['if_show'=>'show']
     * @param  array $localVal 格式化自定义的值，如：[
     *                              'store_logo' => 'image/', // 有'/'为在中间补'/'
     *                              'store_credit' => 'static_image',
     *                              'price' => [
     *                                    'before' => '￥',
     *                                    'after' => '送你的',
     *                               ],
     *                              'time' => [
     *                                    'func' => 'date',
     *                                    'args' => ['Y-m-d', '###'],
     *                                    'before' => '时间：' // 返回结果为字符串才会拼接
     *                               ],
     *                              'avg_zg' => [
     *                                    'func' => function($zg){
     *                                                  return substr(sprintf('%.2f', $zg), 0, -1);
     *                                              }
     *                                    'args' => [],
     *                              ]
     *                         ]
     * @return array
     * @author 敖卓超
     * @since  2015-05-28
     */
    public static function formatDataToApp(array $arr, array $localKey = [], array $localVal = [])
    {
        foreach ($arr as $key => $value) {
            if (is_array($value)) {
                $value = self::formatDataToApp($value, $localKey, $localVal);
            }
            //处理值
            if (!empty($localVal)) {
                if (array_key_exists($key, $localVal)) { // 需要修改的值
                    if (is_array($localVal[$key])) { // 数组
                        $action = $localVal[$key];
                        // 回调
                        if (array_key_exists('func', $action)) {
                            $func_arr = [];
                            if (isset($action['args']) && !empty($action['args'])) {
                                array_walk($action['args'], function ($var) use ($value,&$func_arr) {
                                    $func_arr[] = ($var === '###') ? $value : $var;
                                });
                            } else {//空，只用一个参数
                                $func_arr = [$value];
                            }
                            $value = call_user_func_array($action['func'], $func_arr);
                        }
    
                        if (is_string($value)) {
                            // 拼接
                            $before = array_key_exists('before', $action) ? $action['before'] : '';
                            $after  = array_key_exists('after', $action) ? $action['after'] : '';
                            $value  = $before . $value . $after;
                        }
                    } else { // 字符串
                        $value = strval($value);
                        if(!empty($value) || $value === '0'){ // dev改test为兼容本地开发中缓存的问题
                            if (strpos($localVal[$key], 'image') === 0) { // 补全C('IMAGE_URL')
                                $separator = (strrpos($localVal[$key], '/') > 0) ? '/' : '';
                                $value     = str_replace('dev', 'test', C('IMAGE_URL')) . $separator . $value;
                            } elseif (strpos($localVal[$key], 'static_image') === 0) { // 补全C('STATIC_IMG_URL')
                                $separator = (strrpos($localVal[$key], '/') > 0) ? '/' : '';
                                $value     = str_replace('dev', 'test', C('STATIC_IMG_URL')) . $separator . $value;
                            }
                        }
                    }
                }
            }
    
            //删除
            unset($arr[$key]);
            //处理下标
            if (!empty($localKey) && array_key_exists($key, $localKey)) { // 自定义格式化为指定下标
                $arr[$localKey[$key]] = $value;
            } else { // 自动将下划线的驼峰命名改为大写的驼峰命名
                $preg       = preg_replace_callback('/([^_])_(\w)/', function ($match) {
                    return $match['1'] . strtoupper($match['2']);
                }, $key);
                    $arr[$preg] = $value;
            }
        }
    
        return $arr;
    }
}
