<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Debug;

/**
 * 验证SQL规范
 *     只能调用本模块配置文件（config.table.php）中的数据库表
 *
 * @author     SpiritTeam
 * @since      2015年1月9日
 * @version    1.0
 */
class VerifySql implements \Phalcon\Di\InjectionAwareInterface
{

    protected $di;

    /**
     * Sets the dependency injector
     *
     * @param mixed $dependencyInjector
     */
    public function setDI(\Phalcon\DiInterface $dependencyInjector)
    {
        $this->di = $dependencyInjector;
    }

    /**
     * Returns the internal dependency injector
     *
     * @return \Phalcon\DiInterface
     */
    public function getDI()
    {
        return $this->di;
    }

    /**
     * 验证语句
     *
     * @param string $sql
     * @param string $prefix
     */
    public function verify($sql, $prefix)
    {
        return;
        $di = $this->getDI();
        //$moduleName = $di->getDispatcher()->getModuleName();
        
        if (preg_match('/ecm_table_sync/i', $sql) || preg_match('/query_user/i', $sql) || preg_match('/INFORMATION_SCHEMA/i', $sql) || preg_match('/DESCRIBE/i', $sql)) {
            return true;
        }
        
        // 验证like模糊查询
        if (preg_match("/WHERE(.*)(\s+)LIKE(\s+)\'%([^%]*)%\'/i", $sql)) {
            throw new \Exception('SQL查询条件中LIKE不能同时使用左右%号：' . $sql);
        }
        
        // 验证*查询
        if (preg_match('/SELECT(\s+)(((\`(\w+)\`)|(\w+))\.)?\*/i', $sql)) {
            throw new \Exception('SQL查询禁用*符号：' . $sql);
        }
        
        if (preg_match('/order\s+rand/i', $sql)) {
            throw new \Exception('SQL查询禁用rand排序：' . $sql);
        }
        
        if (preg_match('/where.+\sregexp\s+(\'|")/i', $sql)) {
            throw new \Exception('SQL查询禁用正则：' . $sql);
        }
        
        preg_match_all('/' . $prefix . '\w+/i', $sql, $matchs);
        if (empty($matchs[0])) {
            throw new \Exception('SQL查询不包含任何表：' . $sql);
        }
        
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15);
        $called = $backtrace[10];
        if (isset($called['class']) && $called['class'] == 'Phalcon\Mvc\Model\MetaData'){
            $called = $backtrace[13];
        }
        !isset($called['class']) && $called = $backtrace[9];
        $called['class'] == 'Swallow\Service\LogicProxy' && $called = $backtrace[8];
        $module = explode('\\', $called['class']);
        $module = strtolower($module[0]);
        $file = ROOT_PATH . '/application/' . $module . '/config/' . APPLICATION_ENV . '/table.php';
        $tables = is_file($file) ? include $file : [];
        //$tables = $di->getConfig()->table->toArray();
        $noAllow = array_diff($matchs[0], $tables);
        if (! empty($noAllow)) {
            throw new \Exception('SQL查询语句包含不允许查询的表 ：' . $sql);
        }
        
        preg_match_all('/ join /i', $sql, $matchs);
        if (count($matchs[0]) > 5) {
            throw new \Exception('SQL联表不能超过5个：' . $sql);
        }
        
        $modules = $di['application']->getApplication()->getmodules();
        $data = array();
        foreach ($modules as $module => $val) {
            $file = ROOT_PATH . '/application/' . $module . '/config/' . APPLICATION_ENV . '/table.php';
            if (in_array($module, ['common']) || ! is_file($file)) {
                continue;
            }
            $table = include $file;
            $intersect = array_intersect($data, $table);
            if ($intersect) {
                throw new \Exception('模块' . $module . '内定义的表（“' . implode('”，“', $intersect) . '”）与其它模块重复！');
            }
            $data = array_merge($data, $table);
        }
    }
}
