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
class ClientInfo
{

    /**
     * 用户登录信息
     * @var string
     */
    static $uesrLoginToken = '';

    /**
     * 客户端信息数据
     * ['client_name' => 'IOS', 'client_version' => '3.2','client_user_type' => 'seller']
     * 
     * @var array
     */
    static $clientInfo = [
        'client_name' => '',
        'client_version' => '',
        'client_user_type' => ''
    ];

    /**
     * 校验登陆
     * 
     * @param array $args 参数
     * @author zengzhihao<zengzhihao@eelly.net>
     * @since  2015年12月8日
     */
    static public function verifyLogin()
    {
//        $clientName = strtolower(self::$clientInfo['client_name']);
//        $cache = Cache::getInstance('userlogin');
//        $cacheKey = self::$uesrLoginToken;
//        $isLoseLogin = false;
        
//        if (in_array($clientName, ['ios', 'android'])) {
//            if(empty(self::$uesrLoginToken) ) {
//                $isLoseLogin = true;
//            }else{
//                $userLoginInfo = $cache->get($cacheKey,'user_login_info');
//                if(!empty($userLoginInfo) || $userLoginInfo['dateline'] > time()){
//                    $isLoseLogin = true;
//                }
//            }
//        }
        
        //验证登陆不通过
//        if($isLoseLogin === true){
//            throw new LogicException(StatusCodeInfo::USER_LOGIN_LOSE, StatusCode::DATA_NOT_FOUND);
//        }
    }
}
    
