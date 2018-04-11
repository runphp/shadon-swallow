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
 * Amqp适配器
 *
 * @author SpiritTeam
 * @since 2015年3月11日
 */
class Amqp implements \Swallow\Mq\Mq
{

    /**
     *
     * @var \AMQPConnection
     */
    private $connection = null;

    /**
     *
     * @var \AMQPChannel
     */
    private $channel = null;

    /**
     *
     * @var \AMQPExchange
     */
    private $exchange = null;

    /**
     *
     * @var \AMQPQueue
     */
    private $queue = null;

    /**
     * array of strings required to connect
     *
     * @var array
     */
    private $options = array();

    /**
     *
     * @param array $options
     */
    public function __construct($options = array())
    {
        $this->options = $options;
        $connection = new \AMQPConnection();
        $connection->setHost(isset($options['host']) ? $options['host'] : '127.0.0.1');
        $connection->setPort(isset($options['port']) ? $options['port'] : 5672);
        $connection->setLogin(isset($options['login']) ? $options['login'] : 'guest');
        $connection->setPassword(isset($options['password']) ? $options['password'] : 'guest');
        $connection->setVhost(isset($options['vhost']) ? $options['vhost'] : '/');
        ! isset($this->options['exchange_name']) && $this->options['exchange_name'] = 'amq.direct';
        ! isset($this->options['exchange_type']) && $this->options['exchange_type'] = 'direct';
        $this->connection = $connection;
    }

    /**
     * send task
     *
     * @param string $task
     * @param array $args
     * @param string $routingKey
     * @return boolean
     */
    public function send($task, array $args = [], $routingKey = 'default_queue')
    {
        $exchange = $this->getExchange();
        $id = uniqid('php_', true);
        if (array_keys($args) === range(0, count($args) - 1)) {
            $kwargs = array();
        } else {
            $kwargs = $args;
            $args = array();
        }
        $task_array = array('id' => $id, 'task' => $task, 'args' => $args, 'kwargs' => (object) $kwargs);
        $task = json_encode($task_array);
        $params = array('content_type' => 'application/json', 'content_encoding' => 'utf-8', 'timestamp' => time(), 'delivery_mode' => 2);
        if (empty($routingKey)) {
            $routingKey = 'default_queue';
        }
        $this->getQueue($routingKey);
        $success = $exchange->publish($task, $routingKey, 0, $params);
        return $success;
    }

    /**
     * receive task
     *
     * @param string $routingKey
     * @return array
     */
    public function receive($routingKey = 'default_queue')
    {
        $queue = $this->getQueue($routingKey);
        $message = $queue->get(AMQP_AUTOACK);
        if (! $message) {
            return false;
        }
        if ($message->getContentType() != 'application/json') {
            throw new \Exception('Response was not encoded using JSON - found');
        }
        $body = json_decode($message->getBody(), true);
        return $body;
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
        $queue = $this->getQueue($routingKey);
        
        $queue->consume(
            function (\AMQPEnvelope $envelope, \AMQPQueue $queue) use($callback) {
                if ($envelope->getContentType() != 'application/json') {
                    $queue->nack($envelope->getDeliveryTag());
                } else {
                    $body = json_decode($envelope->getBody(), true);
                    call_user_func($callback, $body);
                    $queue->ack($envelope->getDeliveryTag());
                }
            });
    }

    /**
     *
     * @param string $exchangeName
     * @return \AMQPExchange
     */
    public function setExchangeName($exchangeName)
    {
        $exchange = $this->getExchange();
        $exchange->setName($exchangeName);
        
        return $exchange;
    }

    /**
     *
     * @param string $exchangeType
     * @return \AMQPExchange
     */
    public function setExchangeType($exchangeType)
    {
        $exchange = $this->getExchange();
        $exchange->setType($exchangeType);
        
        return $exchange;
    }

    /**
     *
     * @return \AMQPConnection
     */
    private function getConnection()
    {
        if (! $this->connection->isConnected()) {
            $this->connection->connect();
        }
        
        return $this->connection;
    }

    /**
     *
     * @return \AMQPChannel
     */
    private function getChannel()
    {
        if (null === $this->channel) {
            $this->channel = new \AMQPChannel($this->getConnection());
        }
        
        return $this->channel;
    }

    /**
     *
     * @return \AMQPExchange
     */
    private function getExchange()
    {
        if (null === $this->exchange) {
            $this->exchange = new \AMQPExchange($this->getChannel());
            $this->setExchangeName($this->options['exchange_name']);
            $this->setExchangeType($this->options['exchange_type']);
            $this->exchange->setFlags(AMQP_DURABLE);
        }
        
        return $this->exchange;
    }

    private function getQueue($routingKey)
    {
        if (null === $this->queue) {
            $this->queue = new \AMQPQueue($this->getChannel());
            $this->queue->setFlags(AMQP_DURABLE | AMQP_AUTODELETE);
        }
        $this->queue->setName($routingKey);
        $this->queue->declareQueue();
        $this->queue->bind($this->options['exchange_name'], $routingKey);
        return $this->queue;
    }

    /**
     * Shutdown
     *
     * @return void
     */
    public function __destruct()
    {
        if ($this->connection->isConnected()) {
            $this->connection->disconnect();
        }
        unset($this->queue, $this->exchange, $this->channel, $this->connection);
    }
}
