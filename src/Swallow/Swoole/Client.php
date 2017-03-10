<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Swoole;

use Swallow\Swoole\Pack;

/**
 * swoole客户端
 * 
 * @author    范世军<fanshijun@eelly.net>
 * @since     2015年11月6日
 * @version   1.0
 */
class Client
{

    /**
     * @var client
     */
    private $client;
    
    /**
     * @var $serviceUrl
     */
    private static $serviceUrl;

    /**
     * 自动创建客户端
     *
     * @param  string $serviceUrl
     * @return self
     */
    public static function getInstance($serviceUrl)
    {
        self::$serviceUrl = $serviceUrl;
        static $instance = null;
        $instance = isset($instance) ? $instance : new self();
        $instance->connect();
        return $instance;
    }

    /**
     * 构造
     */
    private function __construct()
    {
        $this->client = new \swoole_client(SWOOLE_SOCK_TCP);
        $set = [
            //EOF模式
            'open_eof_check' => true,  //打开EOF检测
            'package_eof' => Eof::PACKAGE_EOF,  //设置EOF
            'package_max_length' => Eof::PACKAGE_MAX_LENGTH,
            //pack模式
            /* 'open_length_check' => true,
             'package_length_offset' => 0,
            'package_length_type' => Pack::HEADER_PACK,
            'package_body_offset' => Pack::HEADER_SIZE,
            'package_max_length' => Pack::$packet_maxlen */
        ];
        $this->client->set($set);
    }

    /**
     * 链接
     * 
     * @throws \Exception
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年11月10日
     */
    public function connect()
    {
        //$localHosts = swoole_get_local_ip();
        $localHosts = explode(':', self::$serviceUrl);
        $host = isset($localHosts[0]) ? $localHosts[0] : '127.0.0.1';
        $port = isset($localHosts[1]) ? $localHosts[1] : '9501';
        if (! $this->client->connect($host, $port, 1)) {
            throw new \Exception("Error: {$fp->errMsg}[{$fp->errCode}]\n");
        }
    }

    /**
     * 调用函数
     *
     * @return mixed
     */
    public function call(array $data)
    {
        $msg = json_encode($data);
        $re = $this->send($msg);
        $re == true ? $data = $this->load() : '';
        return $data;
        
    }

    /**
     * 发送数据
     *
     * @param  string $data
     * @return boolean
     */
    public function send($data)
    {
        $data = Packing::encode($data);
        return $this->client->send($data);
    }

    /**
     * 接收数据
     *
     * @param  int   $length
     * @param  int   $waitall
     * @return string
     */
    public function recv($length = 65535, $waitall = false)
    {
        $str = $this->client->recv($length, $waitall);
        if (false == $str) {
            throw new \Exception('Server failure', $this->client->errCode);
        }
        if ('' == $str) {
            throw new \Exception('Server close');
        }
        return $str;
    }

    /**
     * 读取
     */
    public function load()
    {
        $data = $this->recv();
        Packing::decode($data);
        return $data;
    }
}