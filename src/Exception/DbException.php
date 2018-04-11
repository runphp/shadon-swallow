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
 * 数据库异常
 *
 * 数据库层代码运行时才能发现错误抛出的异常
 *
 * @author    SpiritTeam
 * @since     2015年4月15日
 * @version   1.0
 */
class DbException extends SystemException
{

    /**
     * 需不需要数据库异常
     *
     * @var boolean
     */
    public static $needDbException = false;

    /**
     * 构造方法
     *
     * @param string     $message
     * @param int        $code
     * @param \Exception $previous
     */
    public function __construct($message, $code = StatusCode::DB_SERVER_ERROR, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}