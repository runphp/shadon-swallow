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

namespace Swallow\Toolkit\Net\TencentLive\Service;

use Swallow\Toolkit\Net\TencentLive\TencentLive;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Phalcon\Di;
use Shadon\Di\Injectable;
use Swallow\Core\Conf;

class Service extends Injectable
{
    protected static $instance = null;

    protected $tencentLive = null;

    protected $liveConfig = [];

    public function __construct()
    {
        $this->liveConfig = Conf::get('Live/inc');
        if (empty($this->liveConfig)) {
            throw new \ErrorException('tencentLive config cannot be empty');
        }

        $this->tencentLive = TencentLive::getInstance($this->liveConfig);
        method_exists($this, 'init') && call_user_func([$this, 'init']);
    }

    /**
     * @return static
     */
    public static function getInstance()
    {
        return Di::getDefault()->getShared(static::class);
    }

    protected function getResponse($args)
    {
        $errorMessage = '';
        $callMethod = array_slice(debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS), 1, 1);
        $reader = (new \Phalcon\Annotations\Adapter\Memory())->getMethod($callMethod[0]['class'], $callMethod[0]['function']);
        if (!$reader->has('interface')) {
            throw new \ErrorException($callMethod[0]['function'].' not found interface annotation');
        }

        $annotation = $reader->get('interface');
        $interface = $annotation->getArgument(0);
        $requestMethod = $annotation->getArgument(1) ?: 'get';
        $response = $this->tencentLive->request(strtolower($requestMethod), $interface, $args);

        if (isset($response['ret']) && 0 != $response['ret']) {
            $errorMessage = sprintf('[interface] %s [args] %s [ret] %s,[retCode] %s,[errMsg] %s,[message] %s, [output] %s',
                    $interface,
                    json_encode($args),
                    $response['ret'],
                    $response['retcode'],
                    $response['errmsg'],
                    $response['message'],
                    json_encode($response['output'])
                );
        } elseif (isset($response['code']) && 0 != $response['code']) {
            $errorMessage = sprintf('[interface] %s [args] %s [code] %s,[message] %s',
                    $interface,
                    json_encode($args),
                    $response['code'],
                    $response['message']
                );
        }
        !empty($errorMessage) && $this->log($errorMessage, false);

        return $response;
    }

    protected function setBaseUrl($url)
    {
        $this->tencentLive->setBaseUrl($url);

        return $this;
    }

    /**
     * 记录日志.
     *
     * @param string $message 日志内容
     *
     * @return \Monolog\Boolean
     * @return bool
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since  2017年3月7日
     */
    protected function log(string $message, bool $tryException = false): bool
    {
        static $logger;
        if (null === $logger) {
            $logger = new Logger('tencentLive');
            $logPath = $this->getDI()->getConfig()->logPath;
            $logger->pushHandler(new StreamHandler($logPath.'/Live.'.date('Ymd').'.txt', Logger::INFO));
        }
        $res = $logger->info($message);

        if ($tryException) {
            throw new \ErrorException($message);
        }

        return $res;
    }
}
