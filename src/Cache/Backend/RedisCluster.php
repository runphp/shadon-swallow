<?php

declare(strict_types=1);

/*
 * This file is part of eelly package.
 *
 * (c) eelly.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swallow\Cache\Backend;

use Phalcon\Cache\Backend\Redis;

/**
 * Redis cluster.
 *
 * @author    hehui<hehui@eelly.net>
 *
 * @since     2017年4月26日
 *
 * @version   1.0
 */
class RedisCluster extends Redis
{
    /**
     * (non-PHPdoc).
     *
     * @see \Phalcon\Cache\Backend\Redis::_connect()
     */
    public function _connect(): void
    {
        $options = $this->_options;
        $this->_redis = new RedisClusterResource('', $options['seeds'], $options['timeout'], $options['read_timeout']);
    }

    /**
     * @return RedisClusterResource
     */
    public function getRedis()
    {
        $this->_connect();
        return $this->_redis;
    }
}
