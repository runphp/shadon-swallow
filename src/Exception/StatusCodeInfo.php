<?php
/*
 * PHP version 5.4
 *
 * @copyright Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link      http://www.eelly.com
 * @license   衣联网版权所有
 */
namespace Swallow\Exception;

/**
 * 公用返回状态码信息
 *
 * @author   SpiritTeam
 * @since    2015年12月08日
 * @version  1.0
 */
interface StatusCodeInfo
{
    /**
     * 系统错误
     *
     * @var string
     */
    const SYSTEM_ERROR = '系统繁忙，程序猿正在玩命优化哦~';

    /**
     * 更新提醒
     *
     * @var string
     */
    const APP_UPDATE_REMIND = '抱歉，你当前使用的版本过低,请到系统设置或衣联官网(www.eelly.com)更新应用版本';

    /**
     * 图片上传失败
     *
     * @var string
     */
    const IMAGE_UPLOAD_ERROR = '图片上传失败';

    /**
     * 操作失败提示
     *
     * @var string
     */
    const OPERATE_REDO = '操作失败,请重新操作';

    /**
     * 手机验证码过期
     *
     * @var string
     */
    const MOBILE_VERIFY_CODE = '手机验证码过期，请重新获取';
    
    /**
     * 用户未登陆或者登陆过期
     *
     * @var string
     */
    const USER_LOGIN_LOSE = '您还未登陆';
    
    /**
     * 微信请求报错
     *
     * @var string
     */
    const WECHAT_ERROR = '微信请求报错';
    
    /**
     * 极光推送参数错误
     *
     * @var string
     */
    const JPUSH_PARAM_ERROR = '极光参数错误';
    
    /**
     * 支付密码验证失败
     *
     * @var string
     */
    const PAY_PASSWORD_ERROR = '支付密码验证失败';
    
    /**
     * 支付密码为空
     *
     * @var string
     */
    const PAY_PASSWORD_EMPTY = '支付密码为空';
}