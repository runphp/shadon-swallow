<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Toolkit\Encrypt;
use Swallow\Exception\LogicException;
use Swallow\Exception\StatusCode;

/**
 * 常用对称加密算法类 
 * 支持密钥：长度24位 
 * 支持向量：长度8位  
 * 
 * @author     SpiritTeam
 * @since      2015年1月16日
 * @version    1.0
 */
class DesCrypt
{

    /**
     * 密钥
     * 长度24位
     * @var string
     */
    private $key;

    /**
     * 向量
     * 长度8位 
     * @var string
     */
    private $iv;

    /**
     * 构造函数
     *
     * @param string $key 密钥
     * @param string $iv 向量 默认01234567
     */
    public function __construct($key, $iv = '01234567')
    {
        ! empty($key) && $this->setKey($key);
        ! empty($iv) && $this->setIv($iv);
    }

    /**
     * 获取密钥值
     *
     * @return string 密钥值
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * 设置密钥值
     *
     * @param string $key
     */
    public function setKey($key)
    {
        if (strlen($key) != 24) {
            throw new LogicException('DesCrypt key size must 24 bits', StatusCode::SERVICE_BAD_REQUEST);
        }
        $this->key = $key;
    }

    /**
     * 获取向量值
     * 
     * @return string 向量值
     */
    public function getIv()
    {
        return $this->iv;
    }

    /**
     * 设置向量值
     *
     * @param string $iv
     */
    public function setIv($iv)
    {
        if (strlen($iv) != 8) {
            throw new LogicException('DesCrypt iv size must 8 bits', StatusCode::SERVICE_BAD_REQUEST);
        }
        $this->iv = $iv;
    }

    /**
     * 加密
     * 
     * @param string $str 明文
     * @return string 密文
     */
    function encrypt($str)
    {
        $size = @mcrypt_get_block_size(MCRYPT_3DES, MCRYPT_MODE_CBC);
        $str = $this->pkcs5Pad($str, $size);
        $key = str_pad($this->key, 24, '0');
        $td = @mcrypt_module_open(MCRYPT_3DES, '', MCRYPT_MODE_CBC, '');
        if ($this->iv == '') {
            $iv = @mcrypt_create_iv(@mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        } else {
            $iv = $this->iv;
        }
        @mcrypt_generic_init($td, $key, $iv);
        $data = @mcrypt_generic($td, $str);
        @mcrypt_generic_deinit($td);
        @mcrypt_module_close($td);
        return base64_encode($data);
    }

    /**
     * 解密
     * 
     * @param string $str 密文
     * @return string 明文
     */
    function decrypt($str)
    {
        if (empty($str)) {
            return false;
        }
        
        $str = base64_decode($str);
        
        if ($str === false) {
            return false;
        }
        
        $key = str_pad($this->key, 24, '0');
        $td = @mcrypt_module_open(MCRYPT_3DES, '', MCRYPT_MODE_CBC, '');
        if ($this->iv == '') {
            $iv = @mcrypt_create_iv(@mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        } else {
            $iv = $this->iv;
        }
        $ks = @mcrypt_enc_get_key_size($td);
        @mcrypt_generic_init($td, $key, $iv);
        $decrypted = @mdecrypt_generic($td, $str);
        @mcrypt_generic_deinit($td);
        @mcrypt_module_close($td);
        return $this->pkcs5Unpad($decrypted);
    }

    /**
     * pkcs5Pad
     * 
     * @param string $text
     * @param int $blocksize
     * @return string
     */
    function pkcs5Pad($text, $blocksize)
    {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }

    /**
     * pkcs5Unpad
     * 
     * @param string $text
     * @return boolean|string
     */
    function pkcs5Unpad($text)
    {
        $pad = ord($text{strlen($text) - 1});
        if ($pad > strlen($text)) {
            return false;
        }
        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) {
            return false;
        }
        return substr($text, 0, - 1 * $pad);
    }

    /**
     * 登陆到服务管理系统的用户密码 加密 密钥,最大八位
     * 
     * @param string|array $data 加密的数据
     * @param string $key 加密key
     * @return string
     * @author zengzhihao<zengzhihao@eelly.net>
     * @since  2015年11月25日
     */
    public static function userEncrypt($data, $key = '%query%')
    {
        $prep_code = serialize($data);
        $block = @mcrypt_get_block_size('des', 'ecb');
        if (($pad = $block - (strlen($prep_code) % $block)) < $block) {
            $prep_code .= str_repeat(chr($pad), $pad);
        }
        $encrypt = @mcrypt_encrypt(MCRYPT_DES, $key, $prep_code, MCRYPT_MODE_ECB);
        return MD5(base64_encode($encrypt));
    }
}