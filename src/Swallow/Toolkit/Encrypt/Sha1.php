<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Toolkit\Encrypt;

 use Swallow\Exception\StatusCode;
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
	 */
	public function getSHA1($encryptMsg, $token, $timestamp)
	{
		//排序
		try {
			$array = array($encryptMsg, $token, $timestamp);
			sort($array, SORT_STRING);
			$str = implode($array);
			return array(StatusCode::OK, sha1($str));
		} catch (\Exception $e) {
			return array(StatusCode::UNKNOW_ERROR, null);
		}
	}

}
