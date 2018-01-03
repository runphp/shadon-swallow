<?php

namespace Swallow\Toolkit\Encrypt;

/**
 *  SHA1 签名
 *
 * @author    zengzhihao<zengzhihao@eelly.net>
 * @since     2015年12月1日
 * @version   1.0
 */
class Sha1
{
    /**
     * 用SHA1算法生成安全签名
     *
     * @param string $encryptMsg 密文消息
     * @param string $token 凭证
     * @param string $timestamp 时间戳
     * @param string $nonce 随机字符串
     */
    public static function getSHA1($encryptMsg, $token, $timestamp,$nonce)
    {
        //排序
        try {
            $array = array($encryptMsg, $token, $timestamp,$nonce);
            sort($array, SORT_STRING);
            $str = implode($array);
            return array(200, sha1($str));
        } catch (\Exception $e) {
            return array(513, null);
        }
    }
    
}



?>