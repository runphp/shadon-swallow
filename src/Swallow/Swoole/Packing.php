<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Swoole;

use Swallow\Swoole\Pack;
use Swallow\Swoole\Eof;

/**
 * 拆包解包
 *
 * @author    SpiritTeam
 * @since     2015年2月9日
 * @version   1.0
 */
class Packing
{

    /**
     * 打包数据
     * @param string $data
     * @return string
     */
    public static function encode($data)
    {
        return Pack::encode(Eof::encode($data));
    }

    /**
     * 解析数据
     *
     * @param  string $data
     * @return array
     */
    public static function decode(&$data)
    {
        $header = Pack::decode($data);
        $data = Eof::decode($data);
        if ($header !== false) {
            $header['length'] = $header['length'] - Eof::EOF_SIZE;
        }
        return $header;
    }
}