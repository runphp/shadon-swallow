<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Core;

use Swallow\Debug\TraceBrowser;

/**
 * 日志类
 *
 * @author     SpiritTeam
 * @since      2015年1月12日
 * @version    1.0
 */
class Log
{

    /**
     * 错误类型
     * @var array
     */
    protected static $errorMap = array(
        E_ERROR => 'E_ERROR',
        E_WARNING => 'E_WARNING',
        E_PARSE => 'E_PARSE',
        E_NOTICE => 'E_NOTICE',
        E_CORE_ERROR => 'E_CORE_ERROR',
        E_CORE_WARNING => 'E_CORE_WARNING',
        E_COMPILE_ERROR => 'E_COMPILE_ERROR',
        E_COMPILE_WARNING => 'E_COMPILE_WARNING',
        E_USER_ERROR => 'E_USER_ERROR',
        E_USER_NOTICE => 'E_USER_NOTICE',
        E_USER_WARNING => 'E_USER_WARNING',
        E_USER_DEPRECATED => 'E_USER_DEPRECATED',
        E_STRICT => 'E_STRICT',
        E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
        E_DEPRECATED => 'E_DEPRECATED',
        E_USER_DEPRECATED => 'E_USER_DEPRECATED ');

    /**
     * 日志文件 参数配置
     * 日志目录
     * @var array
     */
    protected static $config = array('is_debug' => false, 'log_path' => LOG_PATH, 'is_whoops' => false);

    /**
     * 日志信息
     * @var array
     */
    protected static $logs = array();

    /**
     * 控制台信息
     * @var array
     */
    protected static $consoles = array();

    /**
     * 初始化
     *
     * @param array $conf
     */
    public static function init(array $conf = array())
    {
        //Whoops::phar();
        self::$config = array_merge(self::$config, $conf);

        if (self::$config['is_debug']) {
            if (self::$config['is_whoops']) {
                self::runWhoops();
            } else {
                // 接管系统错误
                error_reporting(0);
                set_error_handler('Swallow\Core\Log::handler');
            }
        }
        if (! self::$config['is_whoops']) {
            // 没有被捕获的异常记录日志
            set_exception_handler('Swallow\Core\Log::exception');
        }
        // 针对E_ERROR错误和写入错误
        register_shutdown_function('Swallow\Core\Log::shutdown');
    }

    /**
     * 记录日志
     *
     * 应用场景 ：手动记录日志
     * 日志文件名为： app.2015.03.18.log
     * 日志目录为：temp\logs\swallow
     * 如果要自定义文件名，传入第二个参数;
     *
     * @param string $message 日志信息
     * @param string $fileName 文件名
     * @param blood $force 立即写入
     */
    public static function record($message, $fileName = '', $force = false)
    {
        if (! empty($message)) {
            $fileName = $fileName ? $fileName : 'app.' . local_date('Y.m.d');
            self::$logs[$fileName][] = $message;
            if ($force === true) {
                self::save();
            }
        }
    }

    /**
     * 控制台记录日志
     *
     * 应用场景 ：调试输出 (该信息只在控制台输出)
     *
     * @param string $message 日志信息
     * @param string $title 日志标题
     */
    public static function console($message, $title)
    {
        self::$consoles[$title][] = $message;
    }

    /**
     * 日志保存
     *
     * @return void
     */
    private static function save()
    {
        self::write(self::$logs);
        // 保存后清空日志缓存
        self::$logs = array();
    }

    /**
     * 写入日志
     *
     * @param string/array $messages 日志信息
     * @return void
     */
    private static function write($messages)
    {
        if (empty($messages)) {
            return;
        }
        // 自动创建日志目录
        $logsDir = self::$config['log_path'] . '/' . 'swallow/';
        if (! is_dir($logsDir)) {
            mkdir($logsDir, 0755, true);
        }
        foreach ($messages as $fileName => $logs) {
            $destination = $logsDir . $fileName . '.log';
            error_log(implode("\r\n", $logs) . "\r\n", 3, $destination);
        }
    }

    /**
     * Exception Handler 接管错误处理
     * @param int     $errno
     * @param string  $errstr
     * @param string  $errfile
     * @param int     $errline
     * @param mixed   $errcontext
     * @param array   $trace
     * @return void
     */
    public static function handler($errno, $errstr, $errfile, $errline, $errcontext = null, $trace = null)
    {
        $is_swallow = self::isSwallow($errfile);
        $errnoStr = self::errStr($errno);

        if (! $is_swallow) {
            return function_exists('exception_handler') ? exception_handler($errno, $errstr, $errfile, $errline) : true;
        }
        if(false !== strpos($errfile, "/Service/")) {
            throw new \Swallow\Exception\SystemException($errstr,$errno);
        }
        $server = SAPI_MODE == 'cli' ? '' : 'URL:' . $_SERVER['SERVER_NAME'] . $_SERVER["REQUEST_URI"];

        if (self::$config['is_debug']) {
            //输出调试
            $title = $errfile . ':' . $errline . '  ' . $errnoStr . ':' . $errstr;
            $backtrace = isset($trace) ? $trace->getTrace() : debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            $backtrace_message = array();
            foreach ($backtrace as $val) {
                if (isset($val['file']) && isset($val['line'])) {
                    $classType = ! empty($val['class']) && ! empty($val['type']) ? $val['class'] . $val['type'] : '';
                    $backtrace_message[] = $val['file'] . "\tLINE : " . $val['line'] . "\t" . $classType . $val['function'];
                }
            }
            TraceBrowser::group($title, $backtrace_message);

            if (SAPI_MODE == 'cli') {
                $str = 'DATE:' . local_date('Y-m-d H:i:s') . "\r\n" . 'FILE:' . $errfile . ' LINE：' . $errline . "\r\n" . $errnoStr . ':' .
                     $errstr . "\r\n";
            } else {
                $str = '<div style="font-size:14px;text-align:left; border-bottom:1px solid #9cc9e0; border-right:1px solid #9cc9e0;padding:1px 4px;color:#000000;font-family:Arial, Helvetica,sans-serif;">' .
                     'DATE:' . local_date('Y-m-d H:i:s') . '<br/>' . $server . '<br/>' . 'FILE:<font color="blue">' . $errfile .
                     '</font><br/>' . 'LINE:' . $errline . '<br/>' . $errnoStr . ':' . $errstr . '</div>';
            }
            echo $str;
        } else {
            $strMsg = 'DATE:' . local_date('Y-m-d H:i:s') . $server . "\r\n" . 'FILE:' . $errfile . ' LINE：' . $errline . "\r\n" . $errnoStr .
                 ':' . $errstr . "\r\n";
            self::record($strMsg, 'error.' . local_date('Y.m.d'));
        }
    }

    /**
     * 保存日志
     */
    public static function shutdown()
    {
        $e = error_get_last();
        $error = array(E_PARSE, E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR);
        if (! empty($e) && is_array($e) && in_array($e['type'], $error)) {
            self::handler($e['type'], $e['message'], $e['file'], $e['line']);
        }
        self::save();
        if (self::$config['is_debug'] && ini_get('output_buffering') && ! empty(self::$consoles)) {
            foreach (self::$consoles as $key => $consoles) {
                //TraceBrowser::group($key, $consoles);
            }
            self::$consoles = array();
        }
    }

    /**
     * 其它异常捕获处理
     *
     * @param \Exception $e
     */
    public static function exception($e)
    {
        self::handler(E_ERROR, $e->getMessage(), $e->getFile(), $e->getLine(), null, $e);
    }

    /**
     * 日志代码转字串符
     * @param string $code
     * @return string
     */
    private static function errStr($code = E_ALL)
    {
        $str = self::$errorMap[$code];
        return $str;
    }

    /**
     * 判断是否在Swallow框架下
     * @param string $errfile
     * @return bool
     */
    private static function isSwallow($errfile)
    {
        if (empty($errfile)) {
            return false;
        }
        $is_module = strpos($errfile, DIRECTORY_SEPARATOR . 'Module' . DIRECTORY_SEPARATOR);
        $is_swallow = strpos($errfile, DIRECTORY_SEPARATOR . 'Swallow' . DIRECTORY_SEPARATOR);

        return $is_module || $is_swallow ? true : false;
    }

    /**
     * 启动哎呀
     *
     */
    private static function runWhoops()
    {
        if (PHP_SAPI === 'cli') {
            $errorPageHandler = new \Whoops\Handler\PlainTextHandler();
        } elseif (filter_input(INPUT_SERVER, 'HTTP_X_REQUESTED_WITH') === 'xmlhttprequest') {
            $errorPageHandler = new \Whoops\Handler\JsonResponseHandler();
        } else {
            $errorPageHandler = new \Whoops\Handler\PrettyPageHandler();
        }
        $run = new \Whoops\Run();
        $run->pushHandler($errorPageHandler);
        $run->silenceErrorsInPaths("/^((?!(Swallow|Module)).)*$/is", E_ALL | E_NOTICE);
        $run->register();
    }
}