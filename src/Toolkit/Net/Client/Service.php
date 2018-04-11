<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Toolkit\Net\Client;

/**
 * 调用服务
 * 
 * @author     SpiritTeam
 * @since      2015年1月13日
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
     * 服务方法名的版本
     * @var string
     */
    private $version = '';

    /**
     * 客户端
     * @var string
     */
    private $client = '';

    /**
     * 访问令牌
     * @var string
     */
    private $accessToken = '';

    /**
     * 用户登陆token
     * @var string
     */
    private $userLoginToken = '';

    /**
     * 系统名
     * @var string
     */
    private $app = '';

    /**
     * 参数
     * @var array
     */
    private $args;

    /**
     * 客户端名
     * @var string
     */
    private $clientName = '';

    /**
     * 客户端用户类型
     * @var string
     */
    private $clientUserType = '';

    /**
     * 客户端版本
     * @var string
     */
    private $clientVersion = '';

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
     * 是否请求token
     * @var bool
     */
    private $isToken = false;

    /**
     * token加解密密钥
     * @var string
     */
    private $tokenSecret = '%HdoQqwI3sQ3bBnaLReX^hMp';

    /**
     * 构造方法
     * 
     * @param array $config 配置信息
     */
    private function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * 获取Service单例
     * 
     * @param array $config 配置信息
     * @return self
     */
    public static function getInstance(array $config = array())
    {
        if (empty($config['host']) || empty($config['encoding_aes_key']) || empty($config['app_id']) || empty($config['token']) ||
             empty($config['app_secret'])) {
            exit('Service configuration error');
        }
        static $instance = array();
        $key = $config['host'] . $config['app_id'];
        if (! isset($instance[$key])) {
            $instance[$key] = new self($config);
        }
        return $instance[$key];
    }

    /**
     * 运行服务
     * 
     * @return mixed
     */
    public function exec()
    {
        if (empty($this->app)) {
            return array('status' => 400, 'info' => 'must be set service app', 'retval' => '');
        }
        if (empty($this->module)) {
            return array('status' => 400, 'info' => 'must be set service module', 'retval' => '');
        }
        if (empty($this->method)) {
            return array('status' => 400, 'info' => 'must be set service method', 'retval' => '');
        }
        
        $key = $this->isToken ? $this->tokenSecret : $this->config['encoding_aes_key'];
        $this->desCrypt = new \Swallow\Toolkit\Encrypt\DesCrypt($key);
        
        $curl = new \Swallow\Toolkit\Net\Curl();
        $time = time();
        $args['app'] = $this->app;
        $args['service_name'] = $this->module;
        $args['method'] = $this->method;
        $args['time'] = $time;
        ! empty($this->args) && $args['args'] = json_encode($this->args);
        ! empty($this->version) && $args['version'] = $this->version;
        ! empty($this->client) && $args['client'] = $this->client;
        ! empty($this->clientVersion) && $args['client_version'] = $this->clientVersion;
        ! empty($this->clientName) && $args['client_name'] = $this->clientName;
        ! empty($this->userLoginToken) && $args['user_login_token'] = $this->userLoginToken;
        ! empty($this->clientUserType) && $args['client_user_type'] = $this->clientUserType;
        
        if ($this->encrypt) {
            $args = array('data' => $this->encryptData(json_encode($args))); //'media' => '@/home/www/git/mall/f.jpg'3786d1e6846dd41ebda3b6a3dde8db5d
            $curl->headers['Transmission-Mode'] = 'Security';
        }
        
        $curl->headers['Transmission-From'] = $this->accessToken;
        $curl->headers['Transmission-Token'] = $this->isToken;
        $curl->headers['Data-Type'] = $this->dataType; // 返回数据格式
        $curl->post($this->config['host'], $args); //发送请求
        $httpCode = $curl->curlGetInfo('http_code');
        if ('200' != $httpCode) {
            return array('status' => $httpCode ? $httpCode : 500, 'info' => $curl->error(), 'retval' => '');
        }
        $content = $curl->response()['body'];
        $header = $curl->response()['header'];
        $data = json_decode($content, true);
        
        if (! $data) {
            return array('status' => 513, 'info' => 'json cannot be decoded : ' . $content, 'retval' => '');
        }
        
        if ($data['status'] == 200) {
            $retval = $data['retval']['data'];
            $signature = $data['retval']['signature'];
            //验证签名
            $currentSigna = $this->signature(json_encode($retval), $time);
            if ($currentSigna['status'] != 200) {
                return $currentSigna;
            } else {
                if ($currentSigna['retval'] != $signature) {
                    return ['status' => 513, 'info' => 'Signatures do not match  ' . $currentSigna['retval'], 'retval' => ''];
                }
            }
            
            if ($this->encrypt && stripos($header, 'Encrypt: false') === false) {
                // 采用加密传输方式 则解码请求返回响应信息
                $retval = $this->desCrypt->decrypt($retval);
                if (! $retval) {
                    return array('status' => 513, 'info' => 'decryption failure :' . $retval, 'retval' => '');
                }
                
                $retval = json_decode($retval, true);
                if (is_null($retval)) {
                    return array('status' => 513, 'info' => 'json cannot be decoded : ' . $retval, 'retval' => '');
                }
            }
            $data['retval'] = $retval;
        }
        $this->clear();
        return $data;
    }

    /**
     * 生成签名
     *
     * @param string $msg  消息
     * @param string $time 时间
     * @author zengzhihao<zengzhihao@eelly.net>
     * @since  2015年12月1日
     */
    private function signature($msg, $time)
    {
        //生成安全签名
        $sha1 = new \Swallow\Toolkit\Encrypt\Sha1();
        
        $secret = $this->isToken ? $this->tokenSecret : $this->config['token'];
        $array = $sha1->getSHA1($msg, $secret, $time);
        return ['status' => $array[0], 'info' => $array[0] != 200 ? 'signature error!' : '', 'retval' => $array[1]];
    }

    /**
     * 加密数据
     * 8位向量 + 加密数据 + 6位干扰码，再进行strrev 反转字符串
     *
     * @param string $data
     * @return string
     */
    private function encryptData($data)
    {
        $randomkeys = $this->randomkeys(6); // 6位干扰码
        $iv = $this->randomkeys(8); // 8位向量
        $this->desCrypt->setIv($iv); // 动态设置加密向量
        $encryptData = strrev($iv . $this->desCrypt->encrypt($data) . $randomkeys);
        return $encryptData;
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
     * 设置调用服务的方法名的版本
     *
     * @param string $version
     * @return self
     */
    public function version($version)
    {
        $this->version = $version;
        return $this;
    }

    /**
     * 设置访问令牌
     *
     * @param string $version
     * @return self
     */
    public function accessToken($accessToken)
    {
        $this->accessToken = $accessToken;
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
     * 是否请求token
     * 
     * @param bool $bool
     * @author zengzhihao<zengzhihao@eelly.net>
     * @since  2015年12月3日
     */
    public function token($bool)
    {
        $this->isToken = $bool;
        //请求token更换加密密钥
        return $this;
    }

    /**
     * 客户端
     * 
     * @param string $client 客户端
     * @author zengzhihao<zengzhihao@eelly.net>
     * @since  2015年12月3日
     */
    public function client($client)
    {
        $this->client = ucfirst($client);
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
     * 用户登陆token
     *
     * @param string $token
     * @author zengzhihao<zengzhihao@eelly.net>
     * @since  2015年12月9日
     */
    public function userLoginToken($token)
    {
        $this->userLoginToken = $token;
        return $this;
    }

    /**
     * 系统名
     * 
     * @param string $app 系统名
     * @author zengzhihao<zengzhihao@eelly.net>
     * @since  2015年12月10日
     */
    public function app($app)
    {
        $this->app = $app;
        return $this;
    }

    /**
     * 调用的客户端名字
     *
     * @param string $clientName 
     * @param string $clientVersion 
     * @author zengzhihao<zengzhihao@eelly.net>
     * @since  2015年12月3日
     */
    public function clientInfo($clientName, $clientVersion, $clientUserType)
    {
        $this->clientName = $clientName;
        $this->clientVersion = $clientVersion;
        $this->clientUserType = $clientUserType;
        return $this;
    }

    /**
     * 调用的客户端版本
     *
     * @param string $clientName 
     * @author zengzhihao<zengzhihao@eelly.net>
     * @since  2015年12月3日
     */
    public function clientName($clientName)
    {
        $this->clientName = $clientName;
        return $this;
    }

    /**
     * 清理参数 
     */
    private function clear()
    {
        $this->encrypt = true;
        $this->isToken = false;
        $this->accessToken = $this->version = $this->client = '';
        $this->clientName = $this->clientVersion = $this->clientUserType = '';
        unset($this->module, $this->method, $this->args);
    }

    /**
     * 调试工具-解密
     * 
     * @param string $ciphertext 密文串
     * @author zengzhihao<zengzhihao@eelly.net>
     * @since  2015年12月19日
     */
    public function deBugDecrypt($ciphertext)
    {
        $data = strrev($ciphertext);
        $iv = substr($data, 0, 8);
        $desCrypt = new \Swallow\Toolkit\Encrypt\DesCrypt($this->config['encoding_aes_key'], $iv);
        $length = strlen($data) - 14;
        $retval = $desCrypt->decrypt(substr($data, 8, $length));
        if (! $retval) {
            return false;
        }
        $retval = json_decode($retval, true);
        return $retval;
    }

    /**
     * 运行服务
     *
     * @param array  $args  请求参数
     * @return mixed
     */
    public function execDecrypt($args)
    {
        $this->desCrypt = new \Swallow\Toolkit\Encrypt\DesCrypt($this->config['encoding_aes_key']);
        $curl = new \Swallow\Toolkit\Net\Curl();
        
        $time = $args['time'] = time();
        $args = array('data' => $this->encryptData(json_encode($args)));
        
        $curl->headers['Transmission-Mode'] = 'Security';
        $curl->headers['Transmission-From'] = $this->accessToken;
        $curl->headers['Data-Type'] = $this->dataType; // 返回数据格式
        $curl->post($this->config['host'], $args); //发送请求
        $httpCode = $curl->curlGetInfo('http_code');
        if ('200' != $httpCode) {
            return array('status' => $httpCode ? $httpCode : 500, 'info' => $curl->error(), 'retval' => '');
        }
        $content = $curl->response()['body'];
        $header = $curl->response()['header'];
        $data = json_decode($content, true);
        
        if (! $data) {
            return array('status' => 513, 'info' => 'json cannot be decoded : ' . $content, 'retval' => '');
        }
        
        if ($data['status'] == 200) {
            $retval = $data['retval']['data'];
            $signature = $data['retval']['signature'];
            //验证签名
            $currentSigna = $this->signature(json_encode($retval), $time);
            if ($currentSigna['status'] != 200) {
                return $currentSigna;
            } else {
                if ($currentSigna['retval'] != $signature) {
                    return ['status' => 513, 'info' => 'Signatures do not match  ' . $currentSigna['retval'], 'retval' => ''];
                }
            }
            
            if (stripos($header, 'Encrypt: false') === false) {
                // 采用加密传输方式 则解码请求返回响应信息
                $retval = $this->desCrypt->decrypt($retval);
                if (! $retval) {
                    return array('status' => 513, 'info' => 'decryption failure :' . $retval, 'retval' => '');
                }
                
                $retval = json_decode($retval, true);
                if (is_null($retval)) {
                    return array('status' => 513, 'info' => 'json cannot be decoded : ' . $retval, 'retval' => '');
                }
            }
            $data['retval'] = $retval;
        }
        $this->clear();
        return $data;
    }
}
