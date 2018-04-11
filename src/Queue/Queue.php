<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Queue;

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 *
 * @author     SpiritTeam
 * @since      2015年8月13日
 * @version    1.0
 */
class Queue
{

    /**
     * @var \$connection
     */
    private $connection = null;

    /**
     * @var \$channel
     */
    private $channel = null;

    /**
     * @var \$exchangeName
     */
    private $exchangeName = 'phalcon_exchange';

    /**
     * @var \$queueName
     */
    private $queueName = 'phalcon_queue';

    /**
     * @var \$queueName
     */
    private $exchangeType = 'direct';

    /**
     * @var \$consumerTag
     */
    private $consumerTag = '';

    /**
     * array of strings required to connect
     *
     * @var array
     */
    private $options = [];

    /**
     * 构造
     * 
     * @param array $options
     */
    public function __construct($options = array())
    {
        $this->options = $options;
        $host = isset($options['host']) ? $options['host'] : '127.0.0.1';
        $port = isset($options['port']) ? $options['port'] : 5672;
        $user = isset($options['user']) ? $options['user'] : 'guest';
        $password = isset($options['password']) ? $options['password'] : 'guest';
        $vhost = isset($options['vhost']) ? $options['vhost'] : '/';
        
        $insist = isset($options['insist']) ? $options['insist'] : false;
        $login_method = isset($options['login_method']) ? $options['login_method'] : 'AMQPLAIN';
        $login_response = isset($options['login_response']) ? $options['login_response'] : null;
        $locale = isset($options['locale']) ? $options['locale'] : 'en_US';
        $connection_timeout = isset($options['connection_timeout']) ? $options['connection_timeout'] : 3;
        $read_write_timeout = isset($options['read_write_timeout']) ? $options['read_write_timeout'] : 3;
        $context = isset($options['context']) ? $options['context'] : null;
        $keepalive = isset($options['keepalive']) ? $options['keepalive'] : false;
        $heartbeat = isset($options['heartbeat']) ? $options['heartbeat'] : 0;
        
        $this->connection = new AMQPConnection($host, $port, $user, $password, $vhost, $insist, $login_method, $login_response, $locale, $connection_timeout, $read_write_timeout, $context, $keepalive, $heartbeat);
    }

    /**
     * @param string $exchangeName
     */
    public function setExchangeName($exchangeName)
    {
        if (! empty($exchangeName)) {
            $this->exchangeName = $exchangeName;
        }
        return $this;
    }

    /**
     * @param string $queueName
     */
    public function setQueueName($queueName)
    {
        if (! empty($queueName)) {
            $this->queueName = $queueName;
        }
        return $this;
    }

    /**
     * @param string $exchangeType
     */
    public function setExchangeType($exchangeType)
    {
        if (! empty($exchangeType)) {
            $this->exchangeType = $exchangeType;
        }
        return $this;
    }

    /**
     * @param string $consumerTag
     */
    public function setConsumerTag($consumerTag)
    {
        if (! empty($consumerTag)) {
            $this->consumerTag = $consumerTag;
        }
        return $this;
    }

    /**
     * 发送
     * 
     * string|array $data
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年9月11日
     */
    public function send($data)
    {
        if (empty($data)) {
            return false;
        }
        $data = is_array($data) ? json_encode($data) : json_encode([$data]);
        $this->channel = $this->connection->channel();
        $this->channel->queue_declare($this->queueName, false, true, false, false);
        $this->channel->exchange_declare($this->exchangeName, $this->exchangeType, false, true, false);
        $this->channel->queue_bind($this->queueName, $this->exchangeName);
        $msg = new AMQPMessage($data, array('content_type' => 'application/json', 'delivery_mode' => 2));
        $this->channel->basic_publish($msg, $this->exchangeName);
        return true;
    }

    /**
     * receive task
     *
     * @param string $routingKey
     * @return array
     */
    public function receive()
    {
        $this->channel = $this->connection->channel();
        $this->channel->queue_declare($this->queueName, false, true, false, false);
        $this->channel->exchange_declare($this->exchangeName, $this->exchangeType, false, true, false);
        $this->channel->queue_bind($this->queueName, $this->exchangeName);
        $msg = $this->channel->basic_get($this->queueName);
        $this->channel->basic_ack($msg->delivery_info['delivery_tag']);
        return json_decode($msg->body, true);
    }

    /**
     * Consume messages from a queue.
     *
     * @param callable $callback 回调函数参数为消息体数组
     * @param bool $noAck 是否自动删除队列
     * @param string $routingKey
     */
    public function consume($callback, $noAck = false)
    {
        $this->channel = $this->connection->channel();
        $this->channel->queue_declare($this->queueName, false, true, false, false);
        $this->channel->exchange_declare($this->exchangeName, $this->exchangeType, false, true, false);
        $this->channel->queue_bind($this->queueName, $this->exchangeName);
        $this->channel->basic_consume($this->queueName, $this->consumerTag, false, $noAck, false, false, $callback);
        /* while (count($this->channel->callbacks)) {
         $this->channel->wait();
         } */
        if (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
    }

    /**
     * Shutdown
     *
     * @return void
     */
    public function __destruct()
    {
        $this->channel->close();
        $this->connection->close();
    }
}
