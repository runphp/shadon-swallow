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
 *
 * 只能在系统运行时才能发现错误抛出的异常
 *
 * @link     http://php.net/manual/zh/class.runtimeexception.php
 * @author   SpiritTeam
 * @since    2015年4月15日
 * @version  1.0
 */
class SystemException extends \RuntimeException
{

    /**
     * 构造方法
     *
     * @param string     $message
     * @param int        $code
     * @param \Exception $previous
     */
    public function __construct($message, $code = StatusCode::SERVER_ERROR, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}