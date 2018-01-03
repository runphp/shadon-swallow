<?php
/*
 * PHP version 5.5
 *
 * @copyright Copyright (c) 2012-2017 EELLY Inc. (http://www.eelly.com)
 * @link      http://www.eelly.com
 * @license   衣联网版权所有
 */

namespace Swallow\Annotations\Adapter;

use Phalcon\Cache\Frontend\Data as FrontendData;
use Swallow\Cache\Backend\RedisCluster as BackendRedis;

/**
 * Class RedisCluster.
 *
 * Stores the parsed annotations to the Redis database.
 * This adapter is suitable for production.
 *
 * ```
 * use Phalcon\Annotations\Adapter\Redis;
 *
 * $annotations = new RedisCluster([
 *  'seeds' => [
 *      '172.18.107.120:7000',
 *      '172.18.107.120:7001',
 *      '172.18.107.120:7002',
 *      '172.18.107.120:7003',
 *      '172.18.107.120:7004',
 *      '172.18.107.120:7005',
 *  ],
 *  'timeout' => 1.5,
 *  'read_timeout' => 1.5,
 *  'lifetime' => 600,
 * ]);
 * ```
 *
 * @author    hehui<hehui@eelly.net>
 *
 * @since     2017年5月18日
 *
 * @version   1.0
 */
class RedisCluster extends Base
{
    /**
     * @var BackendRedis
     */
    protected $redis;

    /**
     * {@inheritdoc}
     *
     * @param array $options Options array
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);
        $this->redis = new BackendRedis(new FrontendData([
            'lifetime' => $this->options['lifetime'],
        ]), $this->options);
    }

    /**
     * {@inheritdoc}
     *
     * @return BackendRedis
     */
    protected function getCacheBackend()
    {
        return $this->redis;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $key
     *
     * @return string
     */
    protected function prepareKey($key)
    {
        return (string) $key;
    }
}
