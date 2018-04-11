<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Core;

use Swallow\Swallow;

/**
 * 远程调用
 *
 * @author    SpiritTeam
 * @since     2015年6月3日
 * @version   1.0
 */
class Rpc
{

    /**
     * 缓存路径
     * @var string
     */
    public static $cacheDir = '';

    /**
     * 缓存路径
     * @var string
     */
    public static $type = '';

    /**
     * 驱动类参数
     * @var array
     */
    public static $driverSetting = array();

    /**
     * 远程化的模块
     * @var array
     */
    public static $rpcModule = array();

    /**
     * 初始配置
     *
     * @param array $conf
     */
    public static function init(array $conf)
    {
        self::$cacheDir = $conf['cacheDir'];
        self::$type = ucfirst($conf['type']);
        self::$driverSetting = $conf['setting'];
        self::$rpcModule = $conf['module'];
    }

    /**
     * 检查是否服务化的模块
     *
     * @param string $name
     */
    public static function isRpcModule($name)
    {
        return empty(self::$rpcModule) ? false : in_array($name, self::$rpcModule);
    }

    /**
     * 初始加载类文件
     *
     * @param string $className
     */
    public static function loadClass($className)
    {
        $paths = self::$cacheDir . '/' . str_replace('\\', '/', $className) . '.php';
        if (! file_exists($paths) || filemtime(__FILE__) > filemtime($paths)) {
            $content = self::build($className);
            self::putFile($paths, $content);
        }
        return $paths;
    }

    /**
     * 调用Rpc
     *
     * @param  string $class
     * @param  string $method
     * @param  array $agrs
     * @return mixed
     */
    public static function call($class, $method, $agrs)
    {
        $r = self::getRpcType()->call($class, $method, $agrs);
        if (isset($r['extend']) && isset($r['extend']['catch'])) {
            if ($r['extend']['catch']) {
                ;
            } else {
                $r = $r['retval'];
            }
        }
        return $r;
    }

    /**
     * 获取实例
     * @return \Swallow\Driver\Rpc
     */
    private static function getRpcType()
    {
        static $instance = null;
        if (! isset($instance)) {
            $className = '\\Swallow\\Rpc\\' . self::$type;
            $instance = new $className(self::$driverSetting);
        }
        return $instance;
    }

    /**
     * 建立空文件
     *
     * @param string $paths
     * @param string $content
     */
    private static function build($classFullName)
    {
        $content = '<?php';
        $pos = strrpos($classFullName, '\\');
        $className = substr($classFullName, $pos + 1);
        $nameSpace = substr($classFullName, 0, $pos);
        $content .= PHP_EOL . 'namespace ' . $nameSpace . ';';
        $content .= PHP_EOL . 'class ' . $className . ' extends \\Swallow\\Core\\Rpc\\Caller {}';
        return $content;
    }

    /**
     * 写入文件
     *
     * @param string $paths
     * @param string $content
     */
    private static function putFile($paths, $content)
    {
        if (! file_exists($paths)) {
            $start = strlen(self::$cacheDir);
            while (false !== ($search = strpos($paths, '/', $start))) {
                $path = substr($paths, 0, $search);
                if (! file_exists($path)) {
                    mkdir($path);
                }
                $start = $search + 1;
            }
        }
        file_put_contents($paths, $content);
    }
}

namespace Swallow\Core\Rpc;

use Swallow\Core\Rpc;
use Swallow\Traits\Singleton;

/**
 * Rpc调用容器
 *
 * @author    SpiritTeam
 * @since     2015年6月3日
 * @version   1.0
 */
class Caller
{
    use Singleton;

    /**
     * 调用Rpc
     *
     * @param  string $method
     * @param  array $agrs
     * @return mixed
     */
    public function __call($method, $agrs)
    {
        return Rpc::call(get_class($this), $method, $agrs);
    }
}


