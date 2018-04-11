<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Debug;

use Phalcon\DI;

/**
 * 对异常处理进行封装输出(方便调试)
 *
 * @author    何辉<hehui@eely.net>
 * @since     2015年8月25日
 * @version   1.0
 */
class ExceptionHandler implements \Swallow\Bootstrap\BootstrapInterface
{
    protected $di;

    protected $tools = [
        'whoops' => 'handleExceptionByWhoops',
        'symfony' => 'handleExceptionBySymfony',
        'phalcon' => 'handleExceptionByPhalcon',
    ];

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

    public function bootStrap()
    {
        error_reporting(- 1);
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
        ini_set('display_errors', 'Off');
    }

    public function render($exception)
    {
        if (PHP_SAPI === 'cli') {
            dd($exception);
        }
        $di = $this->di;
        $type = 'symfony';
        try {
            $type = $di->getConfig()->debug->tool;
        } catch (\Exception $e) {

        }
        if (! array_key_exists($type, $this->tools)) {
            $type = 'symfony';
        }
        call_user_func([$this, $this->tools[$type]], $exception);
        exit(1);
    }

    public function handleError($level, $message, $file = '', $line = 0, $context = [])
    {
        if (error_reporting() & $level) {
            throw new \ErrorException($message, 0, $level, $file, $line);
        }
    }

    public function handleException($e)
    {
        // 日志记录
        $msg  = '#message: '.$e->getMessage()."\n";
        $msg .= '#code: '.$e->getCode()."\n";
        $msg .= '#file: '.$e->getFile()."\n";
        $msg .= '#line: '.$e->getLine()."\n";
        $msg .= '#trace: '.$e->getTraceAsString()."\n";
        $path = $this->di->getConfig()->path->errorLog;
        if (! file_exists($path)) {
            mkdir($path, 0744, true);
        }
        $application = $this->getDI()->getApplication();
        $appType = $application::APP_TYPE;
        $filePath = $path . '/' . $appType . '_error_' . date('Ymd') . '.log';
        $logger = new \Phalcon\Logger\Adapter\File($filePath);
        // 修改日志格式
        $date = date("Y-m-d H:i:s");
        $formatter = new \Phalcon\Logger\Formatter\Line("[$date][ERROR]\n%message%");
        $logger->setFormatter($formatter);
        $logger->error($msg);
        $logger->close();
        // 非生产并开启了debug
        if (APPLICATION_ENV != 'prod' && APP_DEBUG == true) {
            $this->render($e);
        } else {
            $response = new \Phalcon\Http\Response();
            $response->setStatusCode(500, "Internal PHP Server Error");
            $response->setContent("Internal PHP Server Error");
            $response->send();
        }
    }

    public function handleShutdown()
    {
        if (! is_null($error = error_get_last()) && $this->isFatal($error['type'])) {
            $this->handleException($this->fatalExceptionFromError($error, 0));
        }
    }

    protected function isFatal($type)
    {
        return in_array($type, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE]);
    }

    protected function fatalExceptionFromError(array $error, $traceOffset = null)
    {
        return new \Symfony\Component\Debug\Exception\FatalErrorException($error['message'], $error['type'], 0, $error['file'], $error['line'], $traceOffset);
    }

    /**
     * use whoops.
     */
    private function handleExceptionByWhoops($e)
    {
        $run = new \Whoops\Run();
        if (PHP_SAPI === 'cli') {
            $errorPageHandler = new \Whoops\Handler\PlainTextHandler();
        } elseif (filter_input(INPUT_SERVER, 'HTTP_X_REQUESTED_WITH') === 'xmlhttprequest') {
            $errorPageHandler = new \Whoops\Handler\JsonResponseHandler();
            $errorPageHandler->onlyForAjaxRequests(true);
        } else {
            $errorPageHandler = new \Whoops\Handler\PrettyPageHandler();
            try {
                $request = $this->di['request'];
            } catch (\Exception $e) {
                return;
            }
            // Request info:
            $errorPageHandler->addDataTable('Phalcon Application (Request)', array(
                'URI'         => $request->getScheme().'://'.$request->getServer('HTTP_HOST').$request->getServer('REQUEST_URI'),
                'Request URI' => $request->getServer('REQUEST_URI'),
                'Path Info'   => $request->getServer('PATH_INFO'),
                'Query String' => $request->getServer('QUERY_STRING') ?: '<none>',
                'HTTP Method' => $request->getMethod(),
                'Script Name' => $request->getServer('SCRIPT_NAME'),
                //'Base Path'   => $request->getBasePath(),
                //'Base URL'    => $request->getBaseUrl(),
                'Scheme'      => $request->getScheme(),
                'Port'        => $request->getServer('SERVER_PORT'),
                'Host'        => $request->getServerName(),
            ));
        }
        $run->pushHandler($errorPageHandler);
        echo $run->handleException($e);
    }

    /**
     * use symfony debug.
     */
    private function handleExceptionBySymfony($e)
    {
        $exceptionHandler = new \Symfony\Component\Debug\ExceptionHandler();
        $response = $exceptionHandler->createResponse($e);
        echo $response->getContent();
    }

    /**
     * use phalcon debug.
     */
    private function handleExceptionByPhalcon($e)
    {
        $debug = new \Phalcon\Debug();
        $debug->onUncaughtException($e);
    }
}