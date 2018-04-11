<?php

/*
 * PHP version 5.4
 *
 * @copyright Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link      http://www.eelly.com
 * @license   衣联网版权所有
 */
namespace Swallow\Core;

use Swallow\Mq\ErrorHandler\MqErrorHandler;
use Swallow\Mq\ErrorHandler\MqDefaultErrorHandler;

/**
 * 消息队列
 *
 * 使用说明：
 *
 * 一般使用方式如下
 *
 * <code>
 * $mq = Mq::getInstance();
 *
 * // 发送消息，第一个参数为任务名，第二个参数为任务需要的参数，第三个参数可选（指routing_key）
 * $mq->send('sendEmail', array('title' => '邮件标题', 'content' => '邮件内容！！！！'));
 *
 * // 获取消息,第一个参数可选，必须与发送消息的第三个参数对应
 * $mq->receive();
 * </code>
 *
 * 指定队列名的使用方式如下，需要设置routing_key,发送接收需要routing_key保持一致
 *
 * <code>
 * $mq->send('sendSms', array('content' => '短信内容！！！！'), 'sms_queue');
 *
 * $mq->receive('sms_queue');
 * </code>
 *
 * @author SpiritTeam
 * @since 2015年3月12日
 * @version 1.0
 */
class Mq
{

    /**
     * 消息队列
     *
     * @var \Swallow\Mq\Mq
     */
    private $mq = null;

    /**
     *  队列失败处理对象
     *
     * @var MqErrorHandler
     */
    private $errHandler = null;

    /**
     *  队列失败默认处理对象
     *
     * @var MqErrorHandler
     */
    private $defaultErrHandler = null;

    private function __construct(\Swallow\Mq\Mq $mq)
    {
        $this->mq = $mq;
    }

    /**
     * 获取消息队列
     *
     * 配置信息格式
     * <code>
     * return [
     *     'queue_type' => 'amqp',
     *     'amqp_server' => [
     *         'host' => '172.18.107.96',
     *         'port' => 5672,
     *         'login' => 'guest',
     *         'password' => 'guest',
     *         'vhost' => '/',
     *     ],
     *     'redis_server' => [
     *         'host'     => '172.18.107.98',
     *         'port'     => 6379,
     *         'database' => 15
     *     ]
     * ];
     * </code>
     * <ul>
     *   <li>queue_type 队列的驱动类型，如：amqp和redis</li>
     *   <li>amqp_server rabbitmq服务器配置信息</li>
     *   <li>redis_server rabbitmq服务器配置信息</li>
     * </ul>
     *
     * @param array $options 配置信息
     * @param string $queueType 队列类型
     * @return self
     */
    public static function getInstance($options = array())
    {
        static $instances = [];
        if (empty($options)) {
            if (isset($instances['default'])) {
                //返回默认的实例
                return $instances['default'];
            }
            $queueConf = self::getConf();
            $instanceKey = 'default';
        } else {
            $instanceKey = crc32(serialize($options));
            if (isset($instances[$instanceKey])) {
                return $instances[$instanceKey];
            }
            $queueConf = self::getConf();
            $queueConf = array_merge($options, $queueConf);
        }
        $queueType = ucfirst($queueConf['queue_type']);
        $mqClass = '\\Swallow\\Mq\\' . $queueType;
        $driverOption = $queueConf[$queueConf['queue_type'] . '_server'];
        $instances[$instanceKey] = new self(new $mqClass($driverOption));
        return $instances[$instanceKey];
    }

    /**
     *
     * 设置失败处理接口
     *
     * @param MqErrorHandler $errHandler 队列失败处理对象
     * @return self
     */
    public function setErrorHandle(MqErrorHandler $errHandler)
    {
        $this->errHandler = $errHandler;
        return $this;
    }

    /**
     *
     * 设置默认失败处理接口
     *
     * @param MqErrorHandler $errHandler 队列失败处理对象
     * @return self
     */
    public function setDefaultErrorHandle(MqErrorHandler $errHandler)
    {
        $this->defaultErrHandler = $errHandler;
        return $this;
    }

    /**
     * 获取当前失败处理类
     *
     * @return \Swallow\Mq\ErrorHandler\MqErrorHandler
     */
    public function getErrorHandle()
    {
        if (isset($this->errHandler)) {
            return $this->errHandler;
        }
        if (isset($this->defaultErrHandler)) {
            return $this->defaultErrHandler;
        }
        
        $this->defaultErrHandler = new MqDefaultErrorHandler();
        return $this->defaultErrHandler;
    }

    /**
     * 发送任务给队列
     *
     * @param string $task 任务名
     * @param array $args 任务参数
     */
    public function send($task, array $args = [], $routingKey = 'default_queue')
    {
        try {
            $result = $this->mq->send($task, $args, $routingKey);
        } catch (\Exception $e) {
            $this->getErrorHandle()->afterSend($e->getTrace());
        }
        //发送失败后的处理
        if (! $result) {
            $this->getErrorHandle()->afterSend(func_get_args());
        }
        return $result;
    }

    /**
     * 从队列取出任务
     *
     * @param string $routingKey
     */
    public function receive($routingKey = 'default_queue')
    {
        try {
            return $this->mq->receive($routingKey);
        } catch (\Exception $e) {
            $this->getErrorHandle()->afterReceive([$e->getMessage()]);
            $this->getErrorHandle()->afterReceive($e->getTrace());
        }
    }

    /**
     * Consume messages from a queue.
     *
     *
     * @param callable $callback 回调函数参数为消息体数组
     * @param string $routingKey
     */
    public function consume($callback, $routingKey = 'default_queue')
    {
        try {
            $this->mq->consume($callback, $routingKey);
        } catch (\Exception $e) {
            $this->getErrorHandle()->afterReceive([$e->getMessage()]);
            $this->getErrorHandle()->afterReceive($e->getTrace());
        }
    }

    /**
     * 获取配置信息
     *
     * @return array
     */
    private static function getConf()
    {
        $conf = Conf::get('mq');
        if (! empty($conf)) {
            return $conf;
        }
        // 没有配置就使用商城的RabbitMQ配置
        return [
            'queue_type' => 'amqp', 
            'amqp_server' => [
                'host' => Conf::get('System/inc/RABBITMQ_SERVER_HOST'), 
                'port' => Conf::get('System/inc/RABBITMQ_SERVER_PORT'), 
                'login' => Conf::get('System/inc/RABBITMQ_SERVER_LOGIN'), 
                'password' => Conf::get('System/inc/RABBITMQ_SERVER_PASSWORD'), 
                'vhost' => Conf::get('System/inc/RABBITMQ_SERVER_VHOST')]];
    }
}
