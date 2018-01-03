<?php
/*
 * PHP version 5.5
 *
 * @copyright Copyright (c) 2012-2017 EELLY Inc. (http://www.eelly.com)
 * @link      http://www.eelly.com
 * @license   衣联网版权所有
 */

namespace Swallow\Cache\Backend;

/**
 * @author    hehui<hehui@eelly.net>
 *
 * @since     2017年4月26日
 *
 * @version   1.0
 */
class RedisClusterResource extends \RedisCluster
{
    /**
     * @param unknown $key
     *
     * @return unknown
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since  2017年4月26日
     */
    public function delete($key)
    {
        return $this->del($key);
    }

    /**
     * @param unknown $key
     * @param unknown $lifetime
     *
     * @return unknown
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since  2017年4月26日
     */
    public function settimeout($key, $lifetime)
    {
        return $this->expire($key, $lifetime);
    }
}
