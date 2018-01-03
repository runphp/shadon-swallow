<?php
/*
 * PHP version 5.5
 *
 * @copyright Copyright (c) 2012-2017 EELLY Inc. (http://www.eelly.com)
 * @link      http://www.eelly.com
 * @license   衣联网版权所有
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
    public function _connect()
    {
        $options = $this->_options;
        $this->_redis = new RedisClusterResource(null, $options['seeds'], $options['timeout'], $options['read_timeout']);
    }
}
