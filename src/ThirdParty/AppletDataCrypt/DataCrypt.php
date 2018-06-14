<?php

declare(strict_types=1);

/*
 * This file is part of eelly package.
 *
 * (c) eelly.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swallow\ThirdParty\AppletDataCrypt;

/**
 * 微信小程序用户加密数据的解密.
 *
 * @author  wangjiang <wangjiang@eelly.net>
 *
 * @since   2017年03月21日
 *
 * @version 1.0
 */
class DataCrypt
{
    private $appid;
    private $sessionKey;

    /**
     * 构造函数.
     *
     * @param $sessionKey string 用户在小程序登录后获取的会话密钥
     * @param $appid string 小程序的appid
     *
     * @author  wangjiang <wangjiang@eelly.net>
     *
     * @since   2017年03月21日
     */
    public function __construct($appid, $sessionKey)
    {
        $this->sessionKey = $sessionKey;
        $this->appid = $appid;
    }

    /**
     * 检验数据的真实性，并且获取解密后的明文.
     *
     * @param $encryptedData string 加密的用户数据
     * @param $iv string 与用户数据一同返回的初始向量
     * @param $data string 解密后的原文
     *
     * @return int 成功0，失败返回对应的错误码
     */
    public function decryptData($encryptedData, $iv, &$data)
    {
        if (strlen($this->sessionKey) != 24) {
            return ErrorCode::$IllegalAesKey;
        }
        $aesKey=base64_decode($this->sessionKey);


        if (strlen($iv) != 24) {
            return ErrorCode::$IllegalIv;
        }
        $aesIV = base64_decode($iv);
        $aesCipher = base64_decode($encryptedData);

        $pc = new Prpcrypt($aesKey);
        $result = $pc->decrypt($aesCipher,$aesIV);

        if ($result[0] != 0) {
            return $result[0];
        }

        $dataObj = json_decode($result[1]);
        if( $dataObj  == NULL )
        {
            return ErrorCode::$IllegalBuffer;
        }
        if( $dataObj->watermark->appid != $this->appid )
        {
            return ErrorCode::$IllegalBuffer;
        }
        $data = $result[1];
        return ErrorCode::$OK;
    }
}
