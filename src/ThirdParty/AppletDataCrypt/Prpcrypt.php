<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2017 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */

namespace Swallow\ThirdParty\AppletDataCrypt;

/**
 *
 * @author  wangjiang <wangjiang@eelly.net>
 * @since   2017年03月21日
 * @version 1.0
 *
 */
class Prpcrypt
{
    public $key;

    public function __construct($key)
    {
        $this->key = $key;
    }

    /**
	 * 对密文进行解密
	 * @param string $aesCipher 需要解密的密文
     * @param string $aesIV 解密的初始向量
	 * @return string 解密得到的明文
	 */
	public function decrypt( $aesCipher, $aesIV )
	{
		try {
			$module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
			mcrypt_generic_init($module, $this->key, $aesIV);
			//解密
			$decrypted = mdecrypt_generic($module, $aesCipher);
			mcrypt_generic_deinit($module);
			mcrypt_module_close($module);
		} catch (Exception $e) {
			return array(ErrorCode::$IllegalBuffer, null);
		}
        
		try {
			//去除补位字符
			$pkc_encoder = new PKCS7Encoder;
			$result = $pkc_encoder->decode($decrypted);
		} catch (Exception $e) {
			//print $e;
			return array(ErrorCode::$IllegalBuffer, null);
		}
		return array(0, $result);
	}

}
