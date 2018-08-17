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
use Symfony\Component\Lock\Exception\LockAcquiringException;
use Symfony\Component\Lock\Factory;
use Symfony\Component\Lock\Lock;
use Symfony\Component\Lock\Store\RedisStore;

/**
 * 锁(redis)
 *
 * @author hehui<hehui@eelly.net>
 * @since 2016年11月2日
 * @version 1.0
 */
class Locker
{
    private static $lockFactory;

    private static $locks = [];

    /**
     * @return Factory
     */
    private static function getLockFactory()
    {
        if (null === self::$lockFactory) {
            $redisServer = require CONFIG_PATH . '/config.predis.php';
            $redis = new Client($redisServer['parameters'], $redisServer['options']);
            self::$lockFactory = new Factory(new RedisStore($redis));
        }
        return self::$lockFactory;

    }

    /**
     * @param $resource
     * @param int $ttl
     * @return Lock
     */
    private static function getLock($resource, $ttl = 600)
    {
        if (null === self::$lockFactory) {
            $redisServer = require CONFIG_PATH . '/config.predis.php';
            $redis = new Client($redisServer['parameters'], $redisServer['options']);
            self::$lockFactory = new Factory(new RedisStore($redis));
        }

        if (!isset(self::$locks[$resource])) {
            self::$locks[$resource] = self::$lockFactory->createLock($resource, $ttl);
        }

        return self::$locks[$resource];
    }
    /**
     * 加锁
     *
     *
     * @param string $resource
     * @param number $ttl
     * @author hehui<hehui@eelly.net>
     * @since  2016年11月2日
     */
    public static function lock($resource, $ttl = 600): bool
    {
        $lock = self::getLock($resource, $ttl);
        try {
            return $lock->acquire();
        } catch (LockAcquiringException $e) {
            return false;
        }
    }

    /**
     * 开锁
     *
     *
     * @param string $resource
     * @author hehui<hehui@eelly.net>
     * @since  2016年11月2日
     */
    public static function unLock($resource): void
    {
        $lock = self::getLock($resource);
        $lock->release();
    }
}
