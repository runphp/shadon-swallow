<?php
/*
 * PHP version 5.5
 *
 * @copyright Copyright (c) 2012-2017 EELLY Inc. (http://www.eelly.com)
 * @link      http://www.eelly.com
 * @license   衣联网版权所有
 */

namespace Swallow\Redis;

use Predis\Client;

/**
 * Redis 基类.
 *
 * @author    陈淡华<chendanhua@eelly.net>
 *
 * @since     2016-1-9
 *
 * @version   1.0
 */
class Redis
{
    /**
     * 获取redis类.
     *
     * @param string $serverId 服务id
     *
     * @return Client
     *
     * @author 陈淡华<chendanhua@eelly.net>
     *
     * @since  2016-1-9
     */
    public static function getInstance($serverId = '')
    {
        static $redis = [];
        if (empty($serverId)) {
            $serverId = 'predis';
        } else {
            $serverId = 'predis.'.$serverId;
        }
        if (!isset($redis[$serverId])) {
            $server = require CONFIG_PATH.'/config.'.$serverId.'.php';
            $redis[$serverId] = new Client($server['parameters'], $server['options']);
        }

        return $redis[$serverId];
    }
}
