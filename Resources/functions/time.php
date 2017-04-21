<?php
/*
 * PHP version 5.5
 *
 * @copyright Copyright (c) 2012-2016 EELLY Inc. (http://www.eelly.com)
 * @link      http://www.eelly.com
 * @license   衣联网版权所有
 */
if (! function_exists('milliseconds')) {

    /**
     * 获取当前毫秒
     *
     *
     * @return number
     * @author hehui<hehui@eelly.net>
     * @since  2016年10月7日
     */
    function milliseconds()
    {
        return (int)round(microtime(true) * 1000);
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
