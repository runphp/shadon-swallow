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

if (!function_exists('milliseconds')) {
    /**
     * 获取当前毫秒.
     *
     *
     * @return number
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since  2016年10月7日
     */
    function milliseconds()
    {
        return (int) round(microtime(true) * 1000);
    }
}

if (!function_exists('mongoDate')) {
    /**
     * MongoDB\BSON\UTCDateTime.
     *
     *
     * @param int $milliseconds
     *
     * @return \MongoDB\BSON\UTCDateTime
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since 2017年4月20日
     */
    function mongoDate($milliseconds = null)
    {
        if (null === $milliseconds) {
            $milliseconds = milliseconds();
        }

        return new \MongoDB\BSON\UTCDateTime($milliseconds);
    }
}
