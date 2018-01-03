<?php
/*
 * PHP version 5.5
 *
 * @copyright Copyright (c) 2012-2017 EELLY Inc. (http://www.eelly.com)
 * @link      http://www.eelly.com
 * @license   衣联网版权所有
 */

namespace Swallow\Redis;

use Swallow\Core\Conf;
use Whoops\Exception\ErrorException;

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
     * 构造.
     *
     * @author 陈淡华<chendanhua@eelly.net>
     *
     * @since  2016-1-9
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * 初始化.
     *
     * @author 陈淡华<chendanhua@eelly.net>
     *
     * @since  2016-1-9
     */
    protected function init()
    {
    }

    /**
     * 获取redis类.
     *
     * @param string $configType 配置选项
     *
     * @return \Redis
     *
     * @author 陈淡华<chendanhua@eelly.net>
     *
     * @since  2016-1-9
     */
    public static function getInstance($configType = 'default')
    {
        static $obj = [];
        if (!isset($obj[$configType])) {
            $config = Conf::get('Swallow/redis/'.$configType);
            if (is_array($config)) {
                $obj[$configType] = self::initRedis($config);
            } else {
                $obj[$configType] = new \Redis();
                $arr = explode(':', $config, 2);
                $obj[$configType]->connect($arr[0], $arr[1]);
            }
        }

        return $obj[$configType];
    }

    /**
     * 尝试多次链接.
     *
     * @param array|string $config 配置
     * @param number       $times  尝试次数
     *
     * @return \RedisCluster
     *
     * @author 陈淡华<chendanhua@eelly.net>
     *
     * @since 2016-1-9
     */
    private static function initRedis($config, $times = 3)
    {
        $exception = null;
        try {
            return new \RedisCluster(null, $config['seeds'], $config['timeout'], $config['read_timeout']);
        } catch (\RedisClusterException $e) {
            $exception = $e;
        } catch (\RedisException $e) {
            $exception = $e;
        } catch (ErrorException $e) {
            $exception = $e;
        }
        if ($times > 0) {
            return self::initRedis($config, --$times);
        } else {
            throw $exception;
        }
    }
}
