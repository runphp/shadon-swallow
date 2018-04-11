<?php
/*
 * PHP version 5.4
 *
 * @copyright Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link      http://www.eelly.com
 * @license   衣联网版权所有
 */
namespace Swallow\Mq;

/**
 * 消息队列接口
 * 具体实现类在Swallow\Mq
 *
 * @author SpiritTeam
 * @since 2015年3月11日
 */
interface Mq
{

    /**
     * 发送任务给队列
     *
     * @param string $task 任务名
     * @param array $args 任务参数
     * @param string $routingKey 路由键
     */
    public function send($task, array $args = [], $routingKey = 'default_queue');

    /**
     * 从队列取出任务
     *
     * @param string $routingKey 路由键
     */
    public function receive($routingKey = 'default_queue');

    /**
     * Consume messages from a queue.
     *
     *
     * @param callable $callback 回调函数参数为消息体数组
     * @param string $routingKey
     */
    public function consume($callback, $routingKey = 'default_queue');
}