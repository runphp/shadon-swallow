<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Toolkit\Encrypt;

/**
 * 登录密码加解密
 *
 * @author    wangjiang<wangjiang@eelly.net>
 * @since     2017-4-6
 * @version   1.0
 */
class PasswordHelper
{
    //加密密钥
    private $passKeyArr  =array(
        'pay'   => ">$$#@p^P!&", //支付
        'login' => ">$$#@p^P!&",
        'registerMobile' => ">$$#@p^P!&"
    );

    //密码版本
    private $passVerArr = array(
        'pay' => "001", //支付
        'login' => "002",//登录
        'registerMobile' => "003" //注册
    );

    private $decodePassword; //解密密码（加密后密码）
    private $encodePassword; //加密密码（原密码）
    private $type;     //类型
    private $timestamp; //时间戳

    /**
     *
     * @param $input array()
     * @param decodePassword  加密密码（原密码）
     * @param encodePassword  解密密码（加密后密码）
     * @param timeStamp 时间戳
     * @param type 类型
     */

    public function __construct($input = null)
    {
        $this->decodePassword = isset($input['decodePassword']) ? $input['decodePassword'] : '';
        $this->encodePassword = isset($input['encodePassword']) ? $input['encodePassword'] : '';
        $this->type = $input['type'] ?:'app';
        $this->timestamp = $input['timeStamp'] ?: time();
    }

    /**
     *  验证和解密
     *
     *  加密方式为：base64("001"+base64(key+password)+md5(key+timeStamp))
     *
     * @param $input array()
     * @return bool|string
     *
     */
    public function verifyPassword()
    {

        $key = substr(base64_decode($this->encodePassword), -32); //验证密钥
        $keyVer = substr(base64_decode($this->encodePassword), 0,3); //验证版本
        if($key != md5($this->passKeyArr[$this->type].$this->timestamp) || $keyVer != $this->passVerArr[$this->type]) {
            return false;
        }
        $pass = $this->decodePassword();
        return $pass;
    }

    /**
     *  解密
     *  加密方式为：base64("001"+base64(key+password)+md5(key+timeStamp))
     */
    public function decodePassword()
    {
        $tmpPass = base64_decode($this->encodePassword);
        if(!$tmpPass) {
            return false;
        }
        $tmpPass = substr($tmpPass, 3, -32);
        $tmpPass = base64_decode($tmpPass);
        $pass = substr($tmpPass, 10);
        return $pass;
    }

    /**
     *  加密
     *  加密方式为：base64("001"+base64(key+password)+md5(key+timeStamp))
     */
    public function encodePassword()
    {

        $pass = base64_encode($this->passVerArr[$this->type].base64_encode($this->passKeyArr[$this->type].$this->decodePassword).md5($this->passKeyArr[$this->type].$this->timestamp));
        return $pass;
    }

    /**
     *  防篡改加密
     */
    public function sign($option)
    {
        
        if(is_array($option) && count($option)){
            $str = '';
            foreach( $option as $val) {
                $str = $str . md5($val);
            }
            return md5($str);
        }
        return false;
    }
}