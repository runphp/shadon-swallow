<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
if (! function_exists('dd')) {

    /**
     * 格式化显示出变量并结束.
     *
     * @param  mixed
     * @return void
     */
    function dd()
    {
        array_map(function($x) { (new \Swallow\Debug\Dumper)->dump($x); }, func_get_args());
        die;
    }
}
if (! function_exists('debugLog')) {

    /**
     * debug 日志
     *
     *
     * @param array $context
     * @param string $message
     * @author hehui<hehui@eelly.net>
     * @since 2017年2月20日
     */
    function debugLog($context = array(), $message = 'none')
    {
        return \Swallow\Core\Log::debug($message, (array) $context);
    }
}

if (! function_exists('handleError')) {

    /**
     * Converts generic PHP errors to \ErrorException
     * instances, before passing them off to be handled.
     *
     * This method MUST be compatible with set_error_handler.
     *
     * @param int    $level
     * @param string $message
     * @param string $file
     * @param int    $line
     * @throws ErrorException
     * @author hehui<hehui@eelly.net>
     * @since  2016年12月28日
     */
    function handleError($level, $message, $file = null, $line = null)
    {
        $exception = new \ErrorException($message, /*code*/ $level, /*severity*/ $level, $file, $line);
        throw $exception;
    }
}

if (! function_exists('exception_handler')) {

    /**
     * Exception Handler only for ecmall old code
     *
     * 旧的代码中
     * set_error_handler('exception_handler');
     * 请改为
     * set_error_handler('handleError');
     *
     * @param int $level
     * @param string $message
     * @param string $file
     * @param int $linee
     * @author hehui<hehui@eelly.net>
     * @since 2016年12月28日
     * @deprecated
     *
     */
    function exception_handler($level, $message, $file = null, $line = null)
    {
        if ($level &  (E_ALL ^ E_NOTICE ^ E_WARNING ^ E_STRICT ^ E_DEPRECATED ^ E_USER_ERROR ^ E_USER_WARNING ^ E_USER_NOTICE)) {
            return handleError($level, $message, $file, $line);
        }
    }
}
