<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Core;

use Swallow\Debug\Verify;

/**
 * 读取配置
 *
 * 1、$key => 'System/inc/dbconfig' 获取ROOT_PATH . '/data/config.inc.php'配置文件中dbconfig的值<br/>
 * 2、$key => 'System/inc' 获取ROOT_PATH . '/data/config.inc.php'整个配置文件<br/>
 * 3、$key => 'Swallow/dbconfig/host' 获取SWALLOW_PATH . '/Conf/config.dbconfig.php'配置文件中host的值<br/>
 * 4、$key => 'Swallow/dbconfig' 获取SWALLOW_PATH . '/Conf/config.dbconfig.php'整个配置文件<br/>
 * 5、$key => 'App/table/dbconfig' 获取$appPath . '/Conf/config.table.php'配置文件中dbconfig的值<br/>
 * 6、$key => 'App/table' 获取$appPath . '/Conf/config.table.php'整个配置文件<br/>
 * 6、$key => 'table' 获取$appPath . '/Conf/config.table.php'整个配置文件<br/>
 *
 *
 * @author     SpiritTeam
 * @since      2015年1月12日
 * @version    1.0
 */
class Conf
{

    /**
     * 配置项
     * @var array
     */
    private static $config = array();

    /**
     * 获取配置项
     *
     * @param string $key
     * @return mixed
     */
    public static function get($key)
    {
        if (empty($key)) {
            return null;
        }
        $path = explode("/", $key);
        $ord = ord($path[0]{0}); //小写
        if (count($path) < 2 || $ord > 96 && $ord < 123) {
            $class = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $class = strstr(end($class)['class'], '\\', true);
            array_unshift($path, $class);
        }
        $source = $path[0];
        $fileName = $path[1];
        $name = isset($path[2]) ? $path[2] : '';
        if (! isset(self::$config[$source][$fileName])) {
            self::load($source, $fileName);
        }

        //验证是否跨模块调用
        Verify::getConf($source);

        if ('' != $name && isset(self::$config[$source][$fileName][$name])) {
            return self::$config[$source][$fileName][$name];
        } elseif ('' == $name) {
            return self::$config[$source][$fileName];
        }
        return null;
    }

    /**
     * 获取配置项
     *
     * @param string $source
     * @param string $fileName
     * @return boolean
     */
    private static function load($source, $fileName)
    {
        // 如果有这个源的数据就返回数据，否则就去加载
        self::$config[$source][$fileName] = array();
        $path = '';
        if ('System' == $source) {
            $path = ROOT_PATH . '/data/config.' . $fileName . '.php';
        } elseif ('Swallow' == $source) {
            $path = ROOT_PATH . '/config/config.' . $fileName . '.php';
        } else {
            $path = ROOT_PATH . '/config/module/' . strtolower($source) . '/' . $fileName . '.php';
        }
        if (! empty($path) && file_exists($path)) {
            self::$config[$source][$fileName] = include ($path);
            $mergePath = substr_replace($path, "local_", strrpos($path, "/") + 1, 0);
            if (!empty($mergePath) && file_exists($mergePath)){
                self::$config[$source][$fileName] = array_merge(self::$config[$source][$fileName],include ($mergePath));
            }
            return true;
        } else {
            return false;
        }
    }
}