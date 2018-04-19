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
