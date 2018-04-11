<?php
/*
 * PHP version 5.4
 *
 * @copyright Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link      http://www.eelly.com
 * @license   衣联网版权所有
 */
namespace Swallow\Mq\ErrorHandler;

use Swallow\Core\Log;

/**
 * 默认的错误处理实现
 *
 * @author    SpiritTeam
 * @since     2015年3月13日
 * @version   1.0
 */
class MqDefaultErrorHandler implements MqErrorHandler
{

    /* (non-PHPdoc)
     * @see \Swallow\Mq\ErrorHandler\MqErrorHandler::afterSend()
     */
    public function afterSend(array $arr)
    {
        Log::record('发送失败：' . json_encode($arr), '', true);
    }

    /* (non-PHPdoc)
     * @see \Swallow\Mq\ErrorHandler\MqErrorHandler::afterReceive()
     */
    public function afterReceive(array $arr)
    {
        Log::record('接收失败：' . json_encode($arr), '', true);
    }
}
