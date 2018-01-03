<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Toolkit\Net;

use Swallow\Toolkit\Encrypt\DesCrypt;
use Swallow\Toolkit\Net\Curl;

/**
 * Service Api标准请求类
 * 
 * @author     SpiritTeam
 * @since      2015年6月10日
 * @version    1.0
 */
class Service
{

    /**
     * 配置信息
     * @var string
     */
    private $config = '';

    /**
     * 安全Key 长度必须24位长度字符
     * @var string
     */
    private $secretKey = '';

    /**
     * 加密类实例
     * @var \ServiceLib\DesCrypt
     */
    private $desCrypt = null;

    /**
     * 服务模块 命名空间+类名 如 Store\Model\TestModel
     * @var string
     */
    private $module;

    /**
     * 服务方法名
     * @var string
     */
    private $method;

    /**
     * 参数
     * @var array
     */
    private $args;

    /**
     * 是否加密传输 默认加密
     * @var boolean
     */
    private $encrypt = true;

    /**
     * 返回数据格式 json
     * @var string
     */
    private $dataType = 'json';

    /**
     * 构造方法
     * 
     * @param array $config 配置信息
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $key = $this->config['secret_key'];
        $this->desCrypt = new DesCrypt($key);
    }

    /**
     * 获取Service单例
     * 
     * @param array $config 配置信息
     * @return self
     */
    public static function getInstance(array $config = array())
    {
        if (empty($config) || ! isset($config['host']) || ! isset($config['secret_key']) || ! isset($config['account'])) {
            exit('Service configuration error');
        }
        static $instance = array();
        if (! isset($instance[$config['host']])) {
            $instance[$config['host']] = new self($config);
        }
        return $instance[$config['host']];
    }

    /**
     * 设置调用服务的模块 命名空间+类名 如 Store\Model\TestModel
     * 
     * @param string $module
     * @return self
     */
    public function module($module)
    {
        $this->module = $module;
        return $this;
    }

    /**
     * 设置调用服务的方法名
     * 
     * @param string $method
     * @return self
     */
    public function method($method)
    {
        $this->method = $method;
        return $this;
    }

    /**
     * 设置调用服务的参数
     * 
     * @param array $args
     * @return self
     */
    public function args(array $args)
    {
        $this->args = $args;
        return $this;
    }

    /**
     * 是否加密传输 默认加密
     * 
     * @param  boolean $encrypt
     * @return self
     */
    public function encrypt($encrypt)
    {
        $this->encrypt = $encrypt;
        return $this;
    }

    /**
     * 运行服务
     * 
     * @return mixed
     */
    public function exec()
    {
        $curl = new Curl();
        if (empty($this->module)) {
            return array('status' => 400, 'info' => 'must be set service module', 'retval' => '');
        }
        if (empty($this->method)) {
            return array('status' => 400, 'info' => 'must be set service method', 'retval' => '');
        }
        $args = array('service' => $this->module . '::' . $this->method, 'args' => $this->args, 'time' => time());
        $randomkeys = $this->randomkeys(6); // 6位干扰码
        if ($this->encrypt) {
            $iv = $this->randomkeys(8); // 8位向量
            $this->desCrypt->setIv($iv); // 动态设置加密向量
            // data => 8位向量 + 加密数据 + 6位干扰码 => strrev 反转字符串
            $args = array('data' => strrev($iv . $this->desCrypt->encrypt(json_encode($args)) . $randomkeys));
            $curl->headers['Transmission-Mode'] = 'Security';
        }
        $curl->headers['Transmission-From'] = $randomkeys . md5($this->config['account']);
        $curl->headers['Data-Type'] = $this->dataType; // 返回数据格式
        $curl->post($this->config['host'], $args);
        $httpCode = $curl->curlGetInfo('http_code');
        if ('200' != $httpCode) {
            return array('status' => 500, 'info' => $curl->error(), 'retval' => '');
        }
        $content = $curl->response()['body'];
        if ($this->encrypt) {
            // 采用加密传输方式 则解码请求返回响应信息
            $content = $this->desCrypt->decrypt($content);
            if (! $content) {
                return array('status' => 513, 'info' => 'decryption failure :' . PHP_EOL . $curl->response()['body'], 'retval' => '');
            }
        }
        $data = json_decode($content, true);
        if (! $data) {
            return array('status' => 513, 'info' => 'json cannot be decoded : ' . PHP_EOL . $content, 'retval' => '');
        }
        $this->clear();
        return $data;
    }

    /**
     * 生成长度为$length的随机字符串
     * 
     * @param int $length
     * @return string
     */
    private function randomkeys($length)
    {
        $returnStr = '';
        $pattern = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLOMNOPQRSTUVWXYZ';
        for ($i = 0; $i < $length; $i ++) {
            $returnStr .= $pattern{mt_rand(0, 62)}; // 生成php随机数
        }
        return $returnStr;
    }

    /**
     * 清理参数 
     */
    private function clear()
    {
        $this->encrypt = true;
        unset($this->module, $this->method, $this->args);
    }
}