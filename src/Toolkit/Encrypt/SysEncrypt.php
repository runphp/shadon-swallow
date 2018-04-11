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
 * 提供加密/解密字条串
 * 
 * @author    陈淡华<chendanhua@eelly.net>
 * @since     2015-5-14
 * @version   1.0
 */
class SysEncrypt
{

    /**
     * 编码
     * @param mixed $data 要编码的数据
     */
    public static function encode($data)
    {
        return base64_encode(json_encode($data));
    }

    /**
     * 解码
     * @param string $data 要解码的数据
     */
    public static function decode($data)
    {
        return json_decode(base64_decode($data), true);
    }

    /**
     * 加密算法
     * 
     * @param string $data 需要加密的数据
     * @param string $key 密钥
     * @return string
     */
    public static function encrypt($data, $key)
    {
        $data   = json_encode($data);
        $key    = md5($key);
        $x      = 0;
        $len    = strlen($data);
        $l      = strlen($key);
        $char   = '';
        for ($i = 0; $i < $len; $i ++) {
            if ($x == $l) {
                $x = 0;
            }
            $char .= $key{$x};
            $x ++;
        }
        $str    = '';
        for ($i = 0; $i < $len; $i ++) {
            $str .= chr(ord($data{$i}) + (ord($char{$i})) % 256);
        }
        return base64_encode($str);
    }

    /**
     * 解密算法
     * 
     * @param string $data 需要解密的数据
     * @param string $key 密钥
     * @return string
     */
    public static function decrypt($data, $key)
    {
        $key    = md5($key);
        $x      = 0;
        $data   = base64_decode($data);
        $len    = strlen($data);
        $l      = strlen($key);
        $char   = '';
        for ($i = 0; $i < $len; $i ++) {
            if ($x == $l) {
                $x = 0;
            }
            $char .= substr($key, $x, 1);
            $x ++;
        }
        $str    = '';
        for ($i = 0; $i < $len; $i ++) {
            if (ord(substr($data, $i, 1)) < ord(substr($char, $i, 1))) {
                $str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
            } else {
                $str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
            }
        }
        
        $str    = json_decode($str);
        return $str;
    }

    /**
     * 解密通过encrypt.js脚本加密的字符串
     */
    public static function encryptJsDecode($lamb)
    {
        // 令牌有效
        $lamb = substr_replace($lamb, '', - 16);
        if (preg_match('/[1-9]/', $lamb, $yLen)) {
            $yLen   = current($yLen);
            $z      = substr($lamb, 6);
            $x      = '';
            for ($i = 0; $i < $yLen; ++ $i)
                $x .= $z[$i * 2 + 1];
            
            $x .= substr($z, $yLen * 2);
            
            $code       = base64_decode($x);
            $identity   = substr($code, 0, 16);
            $identity   = str_replace('#', '', $identity);
            $identity   = base64_decode($identity);
            $nowUTC     = gmtime();
            $identity   = (int) $identity;
            $nowUTC     = (int) $nowUTC;
            
            // if($identity < ($nowUTC - 300) || $identity > ($nowUTC + 300))
            //     return false;
            

            $key        = substr($code, 16, 32);
            $code       = substr($code, 48);
            $keyLen     = strlen($key);
            $codeLen    = strlen($code);
            $pw         = '';
            
            for ($i = 0; $i < $codeLen; ++ $i)
                $pw .= $code[$i] ^ $key[$i % $keyLen];
            
            return base64_decode($pw);
        } else
            return false;
    }

    /**
     * 字符串加密解密
     * author linzhigang moved by Heyanwen
     * 
     * @param string $uid 用户id
     * @param string $data 原文
     * @param boolean $isEncrypt 是否加密，默认加密
     * @return string 密文
     * 
     * @author 何砚文<heyanwen@eelly.net>
     * @since  2015-6-1
     */
    private static function basc64StringEncrypt($uid, $data, $isEncrypt = true)
    {
        $key    = 'qsczewaxdrbvgytfhnmjloikup';
        $len    = crc32($uid) % 26;
        $key    = substr($key, $len) . substr($key, 0, $len);
        $code   = array_combine(str_split($key), str_split('polkijmnbhuytgvcfdxzaswqer'));
        $code   = $isEncrypt ? $code : array_flip($code);
        $dataLen = strlen($data);
        for ($i = 0; $i < $dataLen; $i ++) {
            $data{$i} = isset($code[$data{$i}]) ? $code[$data{$i}] : $data{$i};
        }
        return $data;
    }

    /**
     * 封装base64加密
     * 
     * @param string $uid   用户id
     * @param array  $data  原文
     * @return string 密文
     * 
     * @author 何砚文<heyanwen@eelly.net>
     * @since  2015-6-1
     */
    public static function encryptBase64($uid, array $data)
    {
        return self::basc64StringEncrypt($uid, self::encode($data));
    }

    /**
     * 封装base64解密
     * 
     * @param string $uid   用户id
     * @param string $data  密文
     * @return string/array 原文
     * 
     * @author 何砚文<heyanwen@eelly.net>
     * @since  2015-6-1
     */
    public static function decryptBase64($uid, $data)
    {
        return self::decode(self::basc64StringEncrypt($uid, $data, false));
    }
}