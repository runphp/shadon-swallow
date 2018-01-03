<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Toolkit;

/**
 * 验证器
 *
 * @author     SpiritTeam
 * @since      2015年1月12日
 * @version    1.0
 */
class Validator
{

    private static $data = array();

    /**
     * 默认函数列表
     * @var array
     */
    private static $func = array(
        'email',
        'lenght',
        'phone',
        'tell',
        'qq',
        'url',
        'zipCode',
        'money',
        'ip',
        'number',
        'require',
        'equal',
        'preg',
        'function');

    /**
     * 默认错误列表
     * @var array
     */
    private static $msg = array(
        'email' => 'Email格式不正确！',
        'lenght' => '长度不正确！',
        'phone' => '手机格式不正确！',
        'tell' => '电话格式不正确！',
        'qq' => 'QQ格式不正确！',
        'url' => 'Url格式不正确！',
        'zipCode' => '邮编格式不正确！',
        'money' => '金钱格式不正确！',
        'ip' => 'IP格式不正确！',
        'number' => '不是数字！',
        'require' => '不能为空！',
        'equal' => '两者不相等！',
        'preg' => '匹配错误！',
        'function' => '自定义方法验证错误！');

    /**
     * 验证入口
     *
     * 当$msg、$rule存在是是对单个变量进行单项验证
     *
     * @param bool|string $checkData
     * @param bool|string $data
     * @param string $msg
     * @param string $rule
     * @return bool|string
     */
    public static function validate($checkData, $data, $msg = '', $rule = '')
    {
        self::$data = $data;
        if (empty($checkData)) {
            return '参数错误！';
        }
        if (! is_array($checkData) && ! is_array($data)) {
            if (! in_array($checkData, self::$func)) {
                return '验证规则不存在！';
            }
            $callBack = 'self::' . $checkData . 'Valide';
            if (! call_user_func_array($callBack, array($data, $rule))) {
                if (empty($msg)) {
                    return self::$msg[$checkData];
                }
                return $msg;
            }
        } elseif (is_array($checkData) && ! is_array($data)) {
            return self::functionCallBack($checkData, $data);
        } elseif (is_array($checkData) && is_array($data)) {
            foreach ($checkData as $dataKey => $checkVal) {
                $value = isset(self::$data[$dataKey]) ? self::$data[$dataKey] : null;
                if (empty($checkVal)) {
                    break;
                }
                $r = self::functionCallBack($checkVal, $value);
                if ($r !== true) {
                    return $r;
                }
            }
        }
        return true;
    }

    /**
     * 方法執行回調
     *
     * @param  $checkData
     * @param  $data
     * @return multitype:|unknown
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年4月16日
     */
    private static function functionCallBack($checkData, $data)
    {
        foreach ($checkData as $checkRule) {
            $func = $checkRule[0];
            if (! in_array($func, self::$func)) {
                return '验证规则不存在！';
            }
            //调用的方法
            $callBack = ($func == 'function') ? $checkRule[1] : 'self::' . $func . 'Valide';
            if (! call_user_func_array($callBack, array($data, $checkRule[1]))) {
                if (empty($checkRule[2])) {
                    return self::$msg[$func];
                }
                return $checkRule[2];
            }
        }
        return true;
    }

    /**
     * 验证Email
     *
     * @param string $value
     * @param string $rule
     * @return boolean
     */
    private static function emailValide($value, $rule = '')
    {
        $preg = '/([a-z0-9]*[-_\.]?[a-z0-9]+)*@([a-z0-9]*[-_]?[a-z0-9]+)+[\.][a-z]{2,3}([\.][a-z]{2})?/i';
        if (! empty($rule)) {
            $preg = $rule;
        }
        if (preg_match($preg, $value)) {
            return true;
        }
        return false;
    }

    /**
     * 验证长度
     *
     * @param string $value
     * @param string $rule
     * @return boolean
     */
    private static function lenghtValide($value, $rule = '')
    {
        $valueLen = mb_strlen($value, 'utf-8');
        if (stripos($rule, ',') !== false) {
            $ruleArr = explode(',', $rule);
            if (! is_array($ruleArr)) {
                return false;
            }
            if (! is_numeric($ruleArr[0]) && is_numeric($ruleArr[1])) {
                return $valueLen <= $ruleArr[1];
            } elseif (is_numeric($ruleArr[0]) && ! is_numeric($ruleArr[1])) {
                return $valueLen >= $ruleArr[1];
            } elseif (is_numeric($ruleArr[0]) && is_numeric($ruleArr[1])) {
                return $valueLen >= $ruleArr[0] && $valueLen <= $ruleArr[1];
            }
        } else {
            return $valueLen == intval($rule);
        }
    }

    /**
     * 验证手机
     *
     * @param string $value
     * @param string $rule
     * @return boolean
     */
    private static function phoneValide($value, $rule = '')
    {
        $preg = '/^[1-9][\d]{10}$/';
        if (! empty($rule)) {
            $preg = $rule;
        }
        if (preg_match($preg, $value)) {
            return true;
        }
        return false;
    }

    /**
     * 验证电话
     *
     * @param string $value
     * @param string $rule
     * @return boolean
     */
    private static function tellValide($value, $rule = '')
    {
        $preg = '/^([\d]{3}\-[\d]{8})|([\d]{4}\-[\d]{7})$/';
        if (! empty($rule)) {
            $preg = $rule;
        }
        if (preg_match($preg, $value)) {
            return true;
        }
        return false;
    }

    /**
     * 验证qq
     *
     * @param string $value
     * @param string $rule
     * @return boolean
     */
    private static function qqValide($value, $rule = '')
    {
        $preg = '/^[1-9][\d]{4,10}$/';
        if (! empty($rule)) {
            $preg = $rule;
        }
        if (preg_match($preg, $value)) {
            return true;
        }
        return false;
    }

    /**
     * 验证url
     *
     * @param string $value
     * @param string $rule
     * @return boolean
     */
    private static function urlValide($value, $rule = '')
    {
        $preg = '/^(http|https):\/\/[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\':+!]*([^<>\"])*$/';
        if (! empty($rule)) {
            $preg = $rule;
        }
        if (preg_match($preg, $value)) {
            return true;
        }
        return false;
    }

    /**
     * 验证邮编
     *
     * @param string $value
     * @param string $rule
     * @return boolean
     */
    public static function zipCodeValide($value, $rule = '')
    {
        $preg = '/^[0-9]{4,6}$/';
        if (! empty($rule)) {
            $preg = $rule;
        }
        if (preg_match($preg, $value)) {
            return true;
        }
        return false;
    }

    /**
     * 验证是否为合法人民币格式
     *
     * @param string $value
     * @return boolean
     */
    public static function moneyValide($value)
    {
        if (preg_match('/^[0-9]{1,}$/', $value)) {
            return true;
        }
        if (preg_match('/^[0-9]{1,}\.[0-9]{1,2}$/', $value)) {
            return true;
        }
        return false;
    }

    /**
     * 验证IP是否符合要求
     *
     * @param string $value
     * @return boolean
     */
    public static function ipValide($value)
    {
        return (bool) ip2long($value);
    }

    /**
     * 验证数字
     *
     * @param string $value
     * @param string $rule
     * @return boolean
     */
    private static function numberValide($value, $rule = '')
    {
        $preg = '/^[0-9]+$/';
        if (! empty($rule)) {
            $preg = $rule;
        }
        if (preg_match($preg, $value)) {
            return true;
        }
        return false;
    }

    /**
     * 验证允许为空
     *
     * @param string $value
     * @param string $rule
     * @return boolean
     */
    private static function requireValide($value)
    {
        $strlen = strlen(trim($value));
        if (empty($strlen)) {
            return false;
        }
        return true;
    }

    /**
     * 判断两值是否相等
     *
     * @param string $value
     * @param string $rule
     * @return boolean
     */
    private static function equalValide($value, $rule = '')
    {
        return $value == self::$data[$rule];
    }

    /**
     * 正则表达式验证
     *
     * @param string $value
     * @param string $rule
     * @return boolean
     */
    private static function pregValide($value, $rule = '')
    {
        if (empty($rule) || ! preg_match($rule, $value)) {
            return false;
        }
        return true;
    }
}