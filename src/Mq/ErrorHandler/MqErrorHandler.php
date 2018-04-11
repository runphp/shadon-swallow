<?php
/*
 * PHP version 5.4
 *
 * @copyright Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link      http://www.eelly.com
 * @license   衣联网版权所有
 */
namespace Swallow\Mq\ErrorHandler;

/**
 * 队列失败处理接口
 *
 * @author    SpiritTeam
 * @since     2015年3月13日
 * @version   1.0
 */
interface MqErrorHandler
{

    /**
     * 发送任务给队列失败的处理
     *
     * @param array $arr 传递的信息
     */
    public function afterSend(array $arr);

    /**
     * 从队列取出任务失败的处理
     *
     * @param array $arr 传递的信息
     */
    public function afterReceive(array $arr);
}
