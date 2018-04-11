<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Bootstrap;

/**
 * 服务调用统计接口
 *
 * @author 李焯桓 <lizhuohuan@eelly.net>
 * @since 2017年3月12日
 * 
 */
interface ApiStatisticsInterface
{
    /**
     * 原始请求数据
     * 
     * @return string
     * 
     * @author 李焯桓 <lizhuohuan@eelly.net>
     * @since 2017年3月12日
     */
    function getRequestData();
    
    /**
     * 解密请求数据
     * 
     * @return string
     * 
     * @author 李焯桓 <lizhuohuan@eelly.net>
     * @since 2017年3月12日
     */
    function getRequestDataDecrypt();
    
    /**
     * 系统名
     * 
     * @return string
     * 
     * @author 李焯桓 <lizhuohuan@eelly.net>
     * @since 2017年3月12日
     */
    function getApp();
    
    /**
     * 服务类名
     * 
     * @return string
     * 
     * @author 李焯桓 <lizhuohuan@eelly.net>
     * @since 2017年3月12日
     */
    function getServiceName();
    
    /**
     * 服务方法名
     * 
     * @return string
     * 
     * @author 李焯桓 <lizhuohuan@eelly.net>
     * @since 2017年3月12日
     */
    function getMethod();
    
    /**
     * 方法参数
     * 
     * @return array
     * 
     * @author 李焯桓 <lizhuohuan@eelly.net>
     * @since 2017年3月12日
     */
    function getArgs();
    
    /**
     * 客户端用户名。参考[这里](http://servicemanage.eelly.test/common/account)
     * 
     * @return string
     * 
     * @author 李焯桓 <lizhuohuan@eelly.net>
     * @since 2017年3月12日
     */
    function getClientName();
    
    /**
     * 客户端版本
     * 
     * @return string
     * 
     * @author 李焯桓 <lizhuohuan@eelly.net>
     * @since 2017年3月12日
     */
    function getClientVersion();
    
    /**
     * 客户端类型。seller, buyer
     * 
     * @return string
     * 
     * @author 李焯桓 <lizhuohuan@eelly.net>
     * @since 2017年3月12日
     */
    function getClientUserType();
    
    /**
     * 客户端设备ID。针对移动端，可为空
     * 
     * @return string
     * 
     * @author 李焯桓 <lizhuohuan@eelly.net>
     * @since 2017年3月12日
     */
    function getDeviceNumber();
    
    /**
     * 请求具体接口时的token
     * 
     * @return string
     * 
     * @author 李焯桓 <lizhuohuan@eelly.net>
     * @since 2017年3月12日
     */
    function getTransmissionFrom();
    
    /**
     * 终端用户（个人账号）登录的token
     * 
     * @return string
     * 
     * @author 李焯桓 <lizhuohuan@eelly.net>
     * @since 2017年3月12日
     */
    function getUserLoginToken();
    
    /**
     * user_login_token对应的user_id
     * 
     * @return string
     * 
     * @author 李焯桓 <lizhuohuan@eelly.net>
     * @since 2017年3月12日
     */
    function getUserLoginTokenUserId();
    
    /**
     * 处理结果状态码
     * 
     * @return string
     * 
     * @author 李焯桓 <lizhuohuan@eelly.net>
     * @since 2017年3月12日
     */
    function getHandleStatus();
    
    /**
     * 处理结果提示
     * 
     * @return string
     * 
     * @author 李焯桓 <lizhuohuan@eelly.net>
     * @since 2017年3月12日
     */
    function getHandleInfo();
    
    /**
     * 处理结果数据
     * 
     * @return array
     * 
     * @author 李焯桓 <lizhuohuan@eelly.net>
     * @since 2017年3月12日
     */
    function getHandleRetval();
}
