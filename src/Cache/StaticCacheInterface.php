<?php
/*
 * PHP version 5.5
 *
 * @copyright Copyright (c) 2012-2016 EELLY Inc. (http://www.eelly.com)
 * @link      http://www.eelly.com
 * @license   衣联网版权所有
 */
namespace Swallow\Cache;

/**
 * 静态缓存接口
 *
 *
 * @author    hehui<hehui@eelly.net>
 * @since     2016年10月7日
 * @version   1.0
 */
interface StaticCacheInterface
{
    /**
     * 设置缓存
     *
     *
     * @param string $key
     * @param mix $value
     * @param int $expired 过期时间(同memcached的ttl, -1 不过期)
     * @return bool
     * @author hehui<hehui@eelly.net>
     * @since  2016年10月7日
     */
    public static function set($key, $value, $expired = -1);

    /**
     * 获取缓存数据
     *
     * 返回null表示不存在或过期
     *
     * @param string $key
     * @return array|null
     * @author hehui<hehui@eelly.net>
     * @since  2016年10月7日
     */
    public static function get($key);
}
