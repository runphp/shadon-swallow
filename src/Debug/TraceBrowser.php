<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Debug;

use Swallow\Toolkit\Debug\FirePHP;
use Swallow\Toolkit\Debug\ChromePhp;

/**
 * 日志调试类
 * 
 * @author     SpiritTeam
 * @since      2015年1月12日
 * @version    1.0
 */
class TraceBrowser
{

    /**
     * 构造器
     */
    protected final function __construct()
    {
    }

    /**
     * 初始化
     *
     * @param array $conf
     */
    public static function init(array $conf = array())
    {
    }

    /**
     * 调用方法
     *
     * @param string $name
     * @param array  $args
     */
    public static function __callstatic($name, $args)
    {
        //分发
        $browserObject = self::getBrowserObject();
        if (! is_null($browserObject)) {
            call_user_func_array(array($browserObject, $name), $args);
        }
    }

    /**
     * 根据浏览器类型获取对象
     *
     * @return BrowserType
     */
    public static function getBrowserObject()
    {
        static $obj = null;
        if (! isset($obj) && !empty($_SERVER["HTTP_USER_AGENT"])) {
            $agent = $_SERVER["HTTP_USER_AGENT"];
            if (strpos($agent, 'Firefox') !== false) {
                $obj = new FirefoxBrowser();
            } elseif (strpos($agent, 'Chrome') !== false) {
                $obj = new ChromeBrowser();
            }
        }
        return $obj;
    }
}

interface BrowserType
{

    /**
     * 输出信息
     * 
     * @param $log
     */
    public function log($log);

    /**
     * 输出警告
     *
     * @param $log
     */
    public function warn($log);

    /**
     * 输出错误
     *
     * @param $log
     */
    public function error($log);

    /**
     * 输出组模式
     *
     * @param $title
     * @param $data
     */
    public function group($title, $data);

    /**
     * 输出table模式
     *
     * @param $title
     * @param $data
     */
    public function table($title, $data);
}

class ChromeBrowser implements BrowserType
{

    /**
     * 输出信息
     * 
     * @param $log
     * @see \Swallow\Debug\BrowserType::log()
     */
    public function log($log)
    {
        if (is_array($log)) {
            foreach ($log as $val) {
                ChromePhp::log($val);
            }
        } else {
            ChromePhp::log($log);
        }
    }

    /**
     * 输出警告
     * 
     * @param $log
     * @see \Swallow\Debug\BrowserType::warn()
     */
    public function warn($log)
    {
        if (is_array($log)) {
            foreach ($log as $val) {
                ChromePhp::warn($val);
            }
        } else {
            ChromePhp::warn($log);
        }
    }

    /**
     * 输出错误
     * 
     * @param $log
     * @see \Swallow\Debug\BrowserType::error()
     */
    public function error($log)
    {
        if (is_array($log)) {
            foreach ($log as $val) {
                ChromePhp::error($val);
            }
        } else {
            ChromePhp::error($log);
        }
    }

    /**
     * 输出组模式
     * 
     * @param $title
     * @param $data
     * @see \Swallow\Debug\BrowserType::group()
     */
    public function group($title = 'DEBUG', $data)
    {
        ChromePhp::group($title);
        if (is_array($data)) {
            foreach ($data as $log) {
                ChromePhp::log($log);
            }
        } else {
            ChromePhp::log($data);
        }
        ChromePhp::groupEnd();
    }

    /**
     * 输出table模式
     * 
     * @param $title
     * @param $data
     * @see \Swallow\Debug\BrowserType::table()
     */
    public function table($title = 'DEBUG', $data)
    {
        $newData = array();
        if (is_array($data)) {
            foreach ($data as $val) {
                $newData[] = array($val);
            }
        } else {
            $newData[] = array($data);
        }
        ChromePhp::table($newData);
    }
}

class FirefoxBrowser implements BrowserType
{
    //实例对象
    private $firephp = null;

    public function __construct()
    {
        $this->firephp = FirePHP::getInstance(true);
    }

    /**
     * 输出信息
     * 
     * @param $log
     * @see \Swallow\Debug\BrowserType::log()
     */
    public function log($log)
    {
        if (is_array($log)) {
            foreach ($log as $val) {
                $this->firephp->log($val);
            }
        } else {
            $this->firephp->log($log);
        }
    }

    /**
     * 输出警告
     * 
     * @param $log
     * @see \Swallow\Debug\BrowserType::warn()
     */
    public function warn($log)
    {
        if (is_array($log)) {
            foreach ($log as $val) {
                $this->firephp->warn($val);
            }
        } else {
            $this->firephp->warn($log);
        }
    }

    /**
     * 输出错误
     * 
     * @param $log
     * @see \Swallow\Debug\BrowserType::error()
     */
    public function error($log)
    {
        if (is_array($log)) {
            foreach ($log as $val) {
                $this->firephp->error($val);
            }
        } else {
            $this->firephp->error($log);
        }
    }

    /**
     * 输出组模式
     * 
     * @param $title
     * @param $data
     * @see \Swallow\Debug\BrowserType::group()
     */
    public function group($title = 'DEBUG', $data)
    {
        $this->firephp->group($title, array('Color' => '#FF00FF'));
        if (is_array($data)) {
            foreach ($data as $log) {
                $this->firephp->log($log);
            }
        } else {
            $this->firephp->log($data);
        }
        $this->firephp->groupEnd();
    }

    /**
     * 输出table模式
     * 
     * @param $title
     * @param $data
     * @see \Swallow\Debug\BrowserType::table()
     */
    public function table($title = 'DEBUG', $data)
    {
        $newData = array();
        $newData[] = array($title . ' TABLE');
        if (is_array($data)) {
            foreach ($data as $val) {
                $newData[] = array($val);
            }
        } else {
            $newData[] = array($data);
        }
        $this->firephp->table($title, $newData);
    }
}