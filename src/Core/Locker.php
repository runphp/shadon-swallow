<?php
/*
 * PHP version 5.5
 *
 * @copyright Copyright (c) 2012-2016 EELLY Inc. (http://www.eelly.com)
 * @link      http://www.eelly.com
 * @license   衣联网版权所有
 */
namespace Swallow\Core;

use Predis\Client;
use Swallow\Redis\Redis;

/**
 * 锁(redis)
 *
 * @author hehui<hehui@eelly.net>
 * @since 2016年11月2日
 * @version 1.0
 */
class Locker
{

    /**
     * 加锁
     *
     *
     * @param string $lockName
     * @param number $ttl
     * @author hehui<hehui@eelly.net>
     * @since  2016年11月2日
     */
    public static function lock($lockName, $ttl = 600)
    {
        static $redis;
        if (null == $redis) {
            $redisServer = require CONFIG_PATH . '/config.predis.php';
            $redis = new Client($redisServer['parameters'], $redisServer['options']);
        }
        //$redis = Redis::getInstance();
        try {
            $return = $redis->set(
                $lockName,
                getmypid(),
                [
                    'nx',
                    'ex' => $ttl
                ]
            );
        } catch (\RedisClusterException $e) {
            $return = false;
        }

        return $return;
    }

    /**
     * 开锁
     *
     *
     * @param string $lockName
     * @author hehui<hehui@eelly.net>
     * @since  2016年11月2日
     */
    public static function unLock($lockName)
    {
        $redis = Redis::getInstance();
        if ($redis->get($lockName) == getmypid()) {
            return $redis->del($lockName);
        }
        return false;
    }
}
