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

namespace Swallow\Core;

use Swallow\Logger\Handler\DingDingHandler;
use Whoops\Exception\Inspector;
use Whoops\Handler\PlainTextHandler;

/**
 * 日志类.
 *
 * @author     SpiritTeam
 *
 * @since      2015年1月12日
 *
 * @version    1.0
 */
class Log
{
    /**
     * psr logger instance.
     *
     * @var \Psr\Log\LoggerInterface
     */
    private static $logger;

    /**
     * @var array
     */
    private static $config = ['is_debug' => false, 'log_path' => LOG_PATH, 'is_whoops' => false];

    /**
     * @var array
     */
    private static $fatalInfo = [];

    /**
     * reserved memory.
     *
     * @var string
     */
    private static $reservedMemory;

    /**
     * 获取logger.
     *
     * @return \Monolog\Logger
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since 2017年4月7日
     */
    public static function getLogger()
    {
        $loggerName = (self::$config['is_debug'] ? 'dev.' : 'prod.').(PHP_SAPI == 'cli' ? 'cli.' : 'fpm.').getmypid();
        $logger = new \Monolog\Logger($loggerName);
        $stream = LOG_PATH.'/app.'.date('Ymd').'.txt';
        $handler = new \Monolog\Handler\StreamHandler($stream);
        $logger->pushHandler($handler);
        $dingding = require CONFIG_PATH.'/config.dingding.php';
        $logger->pushHandler(new DingDingHandler($dingding['accessToken']));
        if (PHP_SAPI == 'cli' && self::$config['is_debug']) {
            $handler = new \Monolog\Handler\StreamHandler('php://stdout');
            $logger->pushHandler($handler);
        }

        return $logger;
    }

    /**
     * 设置logger.
     *
     *
     * @param \Monolog\Logger $logger
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since  2017年4月7日
     */
    public static function setLogger($logger): void
    {
        self::$logger = $logger;
    }

    /**
     * 初始化.
     *
     * @param array $conf
     */
    public static function init(array $conf = []): void
    {
        self::$config = array_merge(self::$config, $conf);
        self::setLogger(self::getLogger());
        self::runWhoops();
        // 致命错误信息记录
        self::$reservedMemory = str_repeat('x', 40960);
        register_shutdown_function(function ($currPath): void {
            chdir($currPath);
            self::$reservedMemory = null;
            $error = error_get_last();
            if ($error && \Whoops\Util\Misc::isLevelFatal($error['type'])) {
                $context = $error + self::$fatalInfo;
                if (!\Whoops\Util\Misc::isCommandLine()) {
                    $context['request'] = $_REQUEST;
                }
                self::error('custom fatal info', $context);
            }
        }, getcwd());
    }

    /**
     * 自定义php出现致命错误时的信息.
     *
     *
     * @param array $fatalInfo
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since  2017年3月15日
     */
    public static function pushCustomFatalInfo(array $fatalInfo): void
    {
        foreach ($fatalInfo as $key => $value) {
            self::$fatalInfo[$key] = $value;
        }
    }

    /**
     * @param string $message
     * @param string $fileName
     * @param string $force
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since  2016年12月28日
     * @deprecated
     */
    public static function record($message, $fileName = '', $force = false)
    {
        return self::$logger->info($message);
    }

    /**
     * System is unusable.
     *
     * @param string $message
     * @param array  $context
     */
    public static function emergency($message, array $context = [])
    {
        return self::$logger->emergency($message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string $message
     * @param array  $context
     */
    public static function alert($message, array $context = [])
    {
        return self::$logger->alert($message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string $message
     * @param array  $context
     */
    public static function critical($message, array $context = [])
    {
        return self::$logger->critical($message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     * @param array  $context
     */
    public static function error($message, array $context = [])
    {
        return self::$logger->error($message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string $message
     * @param array  $context
     */
    public static function warning($message, array $context = [])
    {
        return self::$logger->warning($message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array  $context
     */
    public static function notice($message, array $context = [])
    {
        return self::$logger->notice($message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string $message
     * @param array  $context
     */
    public static function info($message, array $context = [])
    {
        return self::$logger->info($message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array  $context
     */
    public static function debug($message, array $context = [])
    {
        return self::$logger->debug($message, $context);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level
     * @param string $message
     * @param array  $context
     */
    public static function log($level, $message, array $context = [])
    {
        return self::$logger->log($level, $message, $context);
    }

    /**
     * Logs whoop's Exception.
     *
     *
     * @param \Throwable $e
     */
    public static function logWhoopException(\Throwable $exception): void
    {
        $handler = new PlainTextHandler(self::$logger);
        $handler->loggerOnly(true);
        $handler->setInspector(new Inspector($exception));
        $handler->setException($exception);
        $handler->handle();
    }

    /**
     * 启动哎呀
     */
    private static function runWhoops(): void
    {
        ini_set('display_errors', '0');
        $run = new \Whoops\Run();
        if (self::$config['is_whoops']) {
            error_reporting(E_ALL);
            if (\Whoops\Util\Misc::isCommandLine() || (defined('APP_TYPE') && APP_TYPE == 'service')) {
                $errorPageHandler = new \Whoops\Handler\PlainTextHandler();
            } elseif (\Whoops\Util\Misc::isAjaxRequest()) {
                $errorPageHandler = new \Whoops\Handler\JsonResponseHandler();
                $errorPageHandler->setJsonApi(true);
            } else {
                $errorPageHandler = new \Whoops\Handler\PrettyPageHandler();
            }
            $run->pushHandler($errorPageHandler);
        } else {
            error_reporting(E_ALL ^ E_NOTICE);
        }
        $errorPageHandler = new \Whoops\Handler\PlainTextHandler(self::$logger);
        $errorPageHandler->loggerOnly(true);
        $run->pushHandler($errorPageHandler);
        // ecmall 的代码错误太多暂时过滤掉一些
        if (self::$config['is_debug']) {
            // 开发环境
            $silenceErrors = E_NOTICE;
        } else {
            // 生产环境
            //error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING ^ E_STRICT ^ E_DEPRECATED ^ E_USER_ERROR ^ E_USER_WARNING ^ E_USER_NOTICE);
            $silenceErrors = E_NOTICE | E_STRICT | E_DEPRECATED;
        }
        $run->silenceErrorsInPaths('/^((?!(Swallow|Module)).)*$/is', $silenceErrors);
        $run->allowQuit(false);
        $run->register();
    }
}
