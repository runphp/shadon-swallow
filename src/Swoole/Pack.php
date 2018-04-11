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
class Pack
{

    /**
     * 打包长度
     * @var int
     */
    const HEADER_SIZE = 4;

    /**
     * 打包标设
     * @var string
     */
    const HEADER_STRUCT = "Nlength";

    /**
     * 打包标设
     * @var string
     */
    const HEADER_PACK = "N";

    /**
     * 2M默认最大长度
     * @var int
     */
    public static $packet_maxlen = 2097152;

    /**
     * 打包数据
     * @param string $data
     * @param int    $serid
     * @return string
     */
    public static function encode($data)
    {
        return pack(self::HEADER_PACK, strlen($data)) . $data;
    }

    /**
     * 解析头部
     * 
     * @param  string $data
     * @return array
     */
    public static function decode(&$data)
    {
        $header = substr($data, 0, self::HEADER_SIZE);
        $data = substr($data, self::HEADER_SIZE);
        return $header ? unpack(self::HEADER_STRUCT, $header) : '';
    }
}