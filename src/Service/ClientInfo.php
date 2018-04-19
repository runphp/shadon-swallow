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

namespace Swallow\Service;

/**
 * 客户端信息类.
 *
 * @author     SpiritTeam
 *
 * @since      2015年1月12日
 *
 * @version    1.0
 */
class ClientInfo
{
    /**
     * 用户登录信息.
     *
     * @var string
     */
    private $uesrLoginToken = '';

    /**
     * 客户端信息数据
     * ['client_name' => 'IOS', 'client_version' => '3.2','client_user_type' => 'seller'].
     *
     * @var array
     */
    private $clientInfo = ['client_name' => '', 'client_version' => '', 'client_user_type' => '', 'device_number' => '', 'client_address' => '', 'session_id' => ''];

    private $userLoginInfo = ['uid' => ''];

    private $clearCache = '';

    /**
     * 获取登录用户信息.
     */
    public function setLoginUserInfo(array $info)
    {
        foreach ($info as $key => $val) {
            isset($this->userLoginInfo[$key]) && $this->userLoginInfo[$key] = $val;
        }

        return $this;
    }

    /**
     * 赋值登录用户信息.
     */
    public function assignClientInfo(array $info): void
    {
        foreach ($info as $key => $val) {
            isset($this->userLoginInfo[$key]) && $this->userLoginInfo[$key] = $val;
        }
    }

    /**
     * 获取登录信息.
     */
    public function getLoginUserInfo($field = null)
    {
        if (isset($field)) {
            return isset($this->userLoginInfo[$field]) ? $this->userLoginInfo[$field] : null;
        }

        return $this->userLoginInfo;
    }

    /**
     * 设置客户端信息.
     */
    public function setClientInfo(array $info)
    {
        foreach ($info as $key => $val) {
            isset($this->clientInfo[$key]) && $this->clientInfo[$key] = $val;
        }

        return $this;
    }

    /**
     * 获取客户端信息.
     */
    public function getClientInfo($field = null)
    {
        if (isset($field)) {
            return isset($this->clientInfo[$field]) ? $this->clientInfo[$field] : null;
        }

        return $this->clientInfo;
    }

    /**
     * 设置是否清除缓存.
     */
    public function setClearCache($clearCache)
    {
        $this->clearCache = $clearCache;

        return $this;
    }

    /**
     * 判断是否清除缓存.
     */
    public function getClearCache()
    {
        return $this->clearCache;
    }
}
