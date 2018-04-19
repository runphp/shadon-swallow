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

namespace Swallow\Cache;

/**
 * Cache接口.
 *
 * @author    SpiritTeam
 *
 * @since     2015年3月10日
 *
 * @version   1.0
 */
interface Cache
{
    /**
     * 获取缓存的数据.
     *
     * @param string $key 缓存KEY
     *
     * @return mixed
     */
    public function get($key);

    /**
     * 设置缓存.
     *
     * @param string $key   缓存KEY
     * @param mixed  $value 缓存的内容
     * @param int    $time  缓存KEY前缀
     *
     * @return bool
     */
    public function set($key, $value, $time = '');

    /**
     * 添加缓存.
     *
     * @param string $key   缓存KEY
     * @param mixed  $value 缓存的内容
     * @param int    $time  缓存KEY前缀
     *
     * @return bool
     */
    public function add($key, $value, $time = '');

    /**
     * 删除缓存.
     *
     * @param string $key 缓存KEY
     *
     * @return bool
     */
    public function delete($key);
}
