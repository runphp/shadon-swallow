<?php

/*
 * PHP version 5.4
 *
 * @copyright Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link      http://www.eelly.com
 * @license   衣联网版权所有
 */
namespace Swallow\Rpc;

/**
 * Rpc驱动类
 *
 * @author SpiritTeam
 * @since 2015年3月11日
 */
interface Rpc
{

    /**
     * 调用Rpc
     *
     * @param  string $class
     * @param  string $method
     * @param  array $agrs
     * @return mixed
     */
    public function call($class, $method, $agrs);
}