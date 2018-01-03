<?php
/*
 * PHP version 7.0
 *
 * @copyright Copyright (c) 2012-2017 EELLY Inc. (http://www.eelly.com)
 * @link      http://www.eelly.com
 * @license   衣联网版权所有
 */

// 暂时无法归类的方法

if (! function_exists('isLocalIpAddress')) {

    /**
     * 是否局域网ip
     *
     *
     * @param string $ipAddress
     * @return boolean
     * @author hehui<hehui@eelly.net>
     * @since  2017年4月7日
     */
    function isLocalIpAddress($ipAddress)
    {
        return (! filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE));
    }
}