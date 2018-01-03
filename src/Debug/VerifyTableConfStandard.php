<?php
/*
 * PHP version 5.5
 *
 * @copyright Copyright (c) 2012-2017 EELLY Inc. (http://www.eelly.com)
 * @link      http://www.eelly.com
 * @license   衣联网版权所有
 */

namespace Swallow\Debug;

use Swallow\Core\Cache;
use Swallow\Core\Conf;
use Swallow\Exception\CodeStyleException;
use Swallow\Swallow;

/**
 * 验证table conf规范
 *     只能查找模块配置文件（config.table.php）中的数据库表.
 *
 * @author     SpiritTeam
 *
 * @since      2015年1月9日
 *
 * @version    1.0
 */
class VerifyTableConfStandard
{
    /**
     * 缓存值
     *
     * @var string
     */
    private static $cacheKey = '_vf_check_table_conf';

    /**
     * 验证语句.
     */
    public static function verify()
    {
        //return true;
        $check = Cache::getInstance()->get(self::$cacheKey, 20);
        if (!empty($check)) {
            return true;
        }
        $data = [];
        $d = dir(Swallow::$path['app']);
        while (false !== ($module = $d->read())) {
            if (in_array($module, ['.', '..', 'Common', 'Test', 'Demo']) || !is_dir(Swallow::$path['app'].'/'.$module)) {
                continue;
            }
            $table = Conf::get($module.'/table');
            $intersect = array_intersect($data, $table);
            $intersect = array_diff($intersect, ['ecm_order_baoxiao_setting']);
            if ($intersect) {
                throw new CodeStyleException('模块'.$module.'内定义的表（“'.implode('”，“', $intersect).'”）与其它模块重复！');
            }
            $data = array_merge($data, $table);
        }

        Cache::getInstance()->set(self::$cacheKey, 'ok', 20);
    }
}
