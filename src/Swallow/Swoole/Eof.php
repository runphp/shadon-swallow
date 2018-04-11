<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Swoole;

/**
 * 数据拆包打包
 * 
 * @author    SpiritTeam
 * @since     2015年2月10日
 * @version   1.0
 */
class Eof
{

    /**
     * EOF长度
     * @var int
     */
    const EOF_SIZE = 7;

    /**
     * EOF
     * @var string
     */
    const PACKAGE_EOF = "\r\nEOF\r\n";

    /**
     * 2M默认最大长度
     * @var int
     */
    const PACKAGE_MAX_LENGTH = 2097152;

    /**
     * 打包数据
     * @param string $data
     * @return string
     */
    public static function encode($data)
    {
        return $data . self::PACKAGE_EOF;
    }

    /**
     * 解析数据
     * 
     * @param  string $data
     * @return array
     */
    public static function decode($data)
    {
        return substr($data, 0, - self::EOF_SIZE);
    }
}