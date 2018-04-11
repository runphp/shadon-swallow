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
 * 返回状态码
 *
 * 状态码参考http状态码，复用新增的原则进行添加，状态码请按从小到大排序
 *
 * 状态码归类：
 * 1.成功(2字头)
 * 2.请求错误(4字头)
 * 3.服务器错误(5字头)
 * 4.逻辑错误(7字头)
 *
 * 请保持文档同步,[文档地址](http://172.18.107.96/svn/support/php/开发文档/返回状态码规范.xls)
 *
 * @author   SpiritTeam
 * @since    2015年4月15日
 * @version  1.0
 */
interface StatusCode
{

    /**
     * 成功
     *
     * @var int
     */
    const SUCCESS = 200;

    /**
     * SUCCESS的别名
     *
     * @var int
     */
    const OK = self::SUCCESS;

    /**
     * 请求参数错误
     *
     * @var int
     */
    const BAD_REQUEST = 400;

    /**
     * 无权限
     *
     * @var int
     */
    const REQUEST_FORBIDDEN = 4403;

    /**
     * 未找到(无此种请求)
     *
     * @var int
     */
    const REQUEST_NOT_FOUND = 404;

    /**
     * 服务请求参数错误
     *
     * @var int
     */
    const SERVICE_BAD_REQUEST = 407;

    /**
     * 请求超时
     *
     * @var int
     */
    const REQUEST_TIME_OUT = 408;

    /**
     * 重复请求
     *
     * @var int
     */
    const REQUEST_CONFLICT = 409;

    /**
     * 服务器未知错误
     *
     * @var int
     */
    const SERVER_ERROR = 500;

    /**
     * 数据库服务器未知错误
     *
     * @var int
     */
    const DB_SERVER_ERROR = 511;

    /**
     * 服务帐号不存在
     *
     * @var int
     */
    const SERVER_ACCOUNT_ERROR = 512;

    /**
     * 服务器解码错误
     * @var int
     */
    const SERVER_DECODE_ERROR = 513;

    /**
     * 未知错误
     *
     * @var int
     */
    const UNKNOW_ERROR = 700;

    /**
     * 方法参数错误
     
     * @var int
     */
    const INVALID_ARGUMENT = 701;

    /**
     * 数据未找到
     *
     * @var int
     */
    const DATA_NOT_FOUND = 702;

    /**
     * 重复的事件
     *
     * @var int
     */
    const DUPLICATE_EVENT = 703;

    /**
     * 溢出（过大）
     *
     * @var int
     */
    const OVER_FLOW = 704;

    /**
     * 溢出（过小）
     *
     * @var int
     */
    const UNDER_FLOW = 705;

    /**
     * 不为空
     *
     * @var int
     */
    const NOT_EMPTY = 706;

    /**
     * access_token 失效
     * 
     * @var int
     */
    const ACCESS_TOKEN_INVALID = 707;

}