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
 * json操作函数
 *
 * @author    lizhuohuan
 * @since     2016年10月11日
 * @version   1.0
 */
class Json
{
    /**
     * json decocode
     *
     * > 空对象不要转换成空数组
     *
     * ###测试数据
     *
     * ```
     * $str = json_encode([
     *   'a'=>"[):][:-o][:@]",
     *   'b'=>[],
     *   'c'=>(object)[],
     *   'd'=>new stdClass(),
     *   'e'=>'{"json":{"content":"json\u6570\u636e\u53d1\u9001\u6d4b\u8bd5"}}',
     *   'f' => [
     *      'a'=>"[):][:-o][:@]",
     *       'b'=>[],
     *       'c'=>(object)[],
     *       'd'=>new stdClass(),
     *       'e'=>'{"json":{"content":"json\u6570\u636e\u53d1\u9001\u6d4b\u8bd5"}}'
     *    ],
     *    'g' => true,
     *    'h' => 123,
     *    'i' => 12.3,
     *    'j' => '123',
     *    'k' => '12.3'
     * ]);
     * ```
     * @param string $json json字符串
     * @param bool $isNotDecode 是否未decode过
     * @return \stdClass|string|\stdClass|array|mixed|string|array|mixed|string|\stdClass
     * @author hehui<hehui@eelly.net>
     * @since  2017年2月17日
     */
    public static function decode2($json, $isNotDecode = true)
    {
        if ($isNotDecode) {
            $json = json_decode($json);
            $isNotDecode = false;
        }
        if (is_object($json)) {
            $json = (array) $json;
            if (empty($json)) {
                static $emptyObject;
                if (null === $emptyObject) {
                    $emptyObject = new \stdClass();
                }
                return $emptyObject;
            } else {
                return self::decode2($json, false);
            }
        } elseif (is_array($json)) {
            if (empty($json)) {
                return [];
            }
            foreach ($json as $key => $value) {
                $json[$key] = self::decode2($value, false);
            }
        } elseif (! is_bool($json)) {
            return (string) $json;
        }
        return $json;
    }

    /**
     * jsonDecode(空對象不處理)
     *
     * @param  string $string
     * @author lizhuohuan<lizhuohuan@eelly.net>
     * @since  2016年10月11日
     */
    public static function decode($string)
    {
        if (is_string($string)
                && strlen($string) > 0
                && ($string[0] === "[" || $string[0] === "{")
            ) {
            $obj = json_decode($string);
            // fix: {"msg":"[):][:-o][:@]"}
            if (JSON_ERROR_SYNTAX === json_last_error()) {
                return $string;
            }
            if (is_null($obj)) {
                return $obj;
            }
        } else {
            $obj = $string;
        }
        if (!is_null($obj) && is_object($obj)) {
            $obj = (array) $obj;
            if (is_array($obj)) {
                if (count($obj)) {
                    foreach ($obj as $k => $v) {
                        $obj[$k] = self::decode($v);
                    }
                    return $obj;
                } else {
                    return (object) [];
                }
            } else {
                return $obj;
            }
        } else if (is_array($obj)) {
            if (count($obj)) {
                foreach ($obj as $k => $v) {
                    $obj[$k] = self::decode($v);
                }
                return $obj;
            } else {
                return [];
            }
        } else if (!is_bool($obj)){
            $obj = (string) $obj;
            return $obj;
        } else {
            return $obj;
        }
    }
    
    /**
     * 
     * @param mixed $value the data to be encoded.
     * @param int $options the encoding options. For more details please refer to
     * <http://www.php.net/manual/en/function.json-encode.php>. Default is `JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE`.
     * @return string the encoding result.
     */
    public static function encode($value, $options = 320)
    {
        return json_encode($value, $options);;
    }
}
