<?php
/*
 * PHP version 5.5
 *
 * @copyright Copyright (c) 2012-2017 EELLY Inc. (http://www.eelly.com)
 * @link      http://www.eelly.com
 * @license   衣联网版权所有
 */

namespace Swallow\Debug;

use Swallow\Core\Conf;
use Swallow\Exception\DbException;

/**
 * 验证SQL规范
 *     只能调用本模块配置文件（config.table.php）中的数据库表.
 *
 * @author     SpiritTeam
 *
 * @since      2015年1月9日
 *
 * @version    1.0
 */
class VerifySqlStandard
{
    /**
     * 验证语句.
     *
     * @param string $sql
     */
    public static function verify($sql)
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 6);
        $called = $backtrace[4];
        if ($called['class'] == 'Swallow\\Base\\Model') {
            $called = $backtrace[5];
        }
        $module = explode('\\', $called['class']);
        $module = $module[0];

        if (preg_match('/SELECT(\s+)(((\`(\w+)\`)|(\w+))\.)?\*/i', $sql)) {
            throw new DbException($called['class'].'->'.$called['function'].'里的查询禁用*符号');
        }

        if (preg_match('/order\s+rand/i', $sql)) {
            throw new DbException($called['class'].'->'.$called['function'].'里的查询禁用rand排序');
        }

        if (preg_match('/where.+\sregexp\s+(\'|")/i', $sql)) {
            throw new DbException($called['class'].'->'.$called['function'].'里的查询禁用正则');
        }

        $settings = Conf::get($module.'/table');
        preg_match_all('/'.DB_PREFIX.'\w+/i', $sql, $matchs);

        if (empty($matchs[0])) {
            preg_match_all('/mobile\w+|tp\w+/i', $sql, $match);
            if (empty($match[0])) {
                throw new DbException($called['class'].'->'.$called['function'].'里的查询不包含任何表');
            }
        }
        if (count(array_unique($matchs[0])) > 6) { //通过伟权的和辉哥的协商，为了应付订单查询6张表旧数据的查询
            throw new DbException($called['class'].'->'.$called['function'].'里联表不能超过6个');
        }
        $noAllow = array_diff($matchs[0], $settings);
        if (!empty($noAllow)) {
            throw new DbException($called['class'].'->'.$called['function'].'里的查询语句包含不充许查询的表'.implode(',', $noAllow).$sql);
        }
    }
}
