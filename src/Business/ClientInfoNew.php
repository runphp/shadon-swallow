<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Business;

use Swallow\Exception\LogicException;
use Swallow\Exception\StatusCodeInfo;
use Swallow\Exception\StatusCode;
use Swallow\Core\Cache;

/**
 * 客户端信息类
 *
 * @author     SpiritTeam
 * @since      2015年1月12日
 * @version    1.0
 */
class ClientInfoNew
{

    /**
     * 用户登录信息
     * @var string
     */
    private $uesrLoginToken = '';

    /**
     * 客户端信息数据
     * ['client_name' => 'IOS', 'client_version' => '3.2','client_user_type' => 'seller']
     * 
     * @var array
     */
    private $clientInfo = ['client_name' => '', 'client_version' => '', 'client_user_type' => '', 'device_number' => '', 'client_address' => '', 'session_id' => ''];

    private $userLoginInfo = ['uid' => ''];

    private $clearCache = '';
    
    /**
     * 获取登录用户信息
     * 
     * @author zengzhihao<zengzhihao@eelly.net>
     * @since  2015年12月29日
     */
    public function setLoginUserInfo($uesrLoginToken)
    {
        if (! empty($uesrLoginToken)) {
            $redis = \Swallow\Redis\Redis::getInstance();
            $userLoginInfo = $redis->hGetAll('UserLoginTokenInfo:'.$uesrLoginToken);
            //$cache = Cache::getInstance('userlogin');
            //$userLoginInfo = $cache->get($uesrLoginToken, 'user_login_info');
            if (empty($userLoginInfo)) {
                    throw new LogicException(StatusCodeInfo::USER_LOGIN_LOSE, StatusCode::USER_ACCESS_TOKEN_INVALID);
            }
            //刷新登录缓存时间
            $tokenExpired = \Swallow\Core\Conf::get('Member/cachestore/userLoginToken')['ttl'];
            $redis->expire($uesrLoginToken, $tokenExpired);
            
            $this->userLoginInfo = $userLoginInfo;
        }
        return $this;
    }

    /**
     * 设置登录用户信息
     *
     * @param array $info 登录用户信息
     * @author lanchuwei<lanchuwei@eelly.net>
     * @since  2015年12月29日
     */
    public function assignClientInfo(array $info)
    {
        foreach ($info as $key => $val) {
            isset($this->userLoginInfo[$key]) && $this->userLoginInfo[$key] = $val;
        }
        return $this;
    }

    /**
     * 获取登录信息
     * 
     * @return \Swallow\Core\mixed
     * @author zengzhihao<zengzhihao@eelly.net>
     * @since  2015年12月29日
     */
    public function getLoginUserInfo($field = null)
    {
        if(isset($field)) {
            return isset($this->userLoginInfo[$field]) ? $this->userLoginInfo[$field] : null;
        }
        return $this->userLoginInfo;
    }

    /**
     * 设置客户端信息
     * 
     * @param array $info 客户端信息
     * @author zengzhihao<zengzhihao@eelly.net>
     * @since  2015年12月29日
     */
    public function setClientInfo(array $info)
    {
        foreach ($info as $key => $val) {
            isset($this->clientInfo[$key]) && $this->clientInfo[$key] = $val;
        }
        return $this;
    }

    /**
     * 获取客户端信息
     * 
     * @author zengzhihao<zengzhihao@eelly.net>
     * @since  2015年12月29日
     */
    public function getClientInfo($field = null)
    {
        if(isset($field)) {
            return isset($this->clientInfo[$field]) ? $this->clientInfo[$field] : null;
        }
        return $this->clientInfo;
    }

    /**
     * 设置是否清除缓存
     */
    public function setClearCache($clearCache)
    {
        $this->clearCache = $clearCache;
        return $this;
    }
    
    /**
     * 判断是否清除缓存
     */
    public function getClearCache()
    {
        return $this->clearCache;
    }
    
}
    
