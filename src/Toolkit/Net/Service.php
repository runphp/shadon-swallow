<?php

declare(strict_types=1);

/*
 * This file is part of eelly package.
 *
 * (c) eelly.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swallow\Toolkit\Net;

use Swallow\Toolkit\Encrypt\DesCrypt;

/**
 * Service Api标准请求类.
 *
 * @author     SpiritTeam
 *
 * @since      2015年6月10日
 *
 * @version    1.0
 */
class Service
{
    /**
     * 配置信息.
     *
     * @var string
     */
    private $config = '';

    /**
     * 安全Key 长度必须24位长度字符.
     *
     * @var string
     */
    private $secretKey = '#1elw?u><y@8&%#$@hr7m~+=';

    /**
     * 加密类实例.
     *
     * @var \ServiceLib\DesCrypt
     */
    private $desCrypt = null;

    /**
     * 服务模块 命名空间+类名 如 Store\Model\TestModel.
     *
     * @var string
     */
    private $module;

    /**
     * 服务方法名.
     *
     * @var string
     */
    private $method;

    /**
     * 参数.
     *
     * @var array
     */
    private $args;

    /**
     * 是否加密传输 默认加密.
     *
     * @var bool
     */
    private $encrypt = true;

    /**
     * 返回数据格式 json.
     *
     * @var string
     */
    private $dataType = 'json';

    /**
     * 新参数.
     *
     * @var array
     */
    private $newArgs = ['is_new' => true];

    /**
     * 是否凤凰之后版本.
     */
    private $isPhinx = true;

    /**
     * 构造方法.
     *
     * @param array $config 配置信息
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $key = $this->config['secret_key'];
        $this->desCrypt = new DesCrypt($key);
        $this->accountDesCrypt = new DesCrypt($this->secretKey);
    }

    /**
     * 获取Service单例.
     *
     * @param array $config 配置信息
     *
     * @return self
     */
    public static function getInstance(array $config = [])
    {
        if (empty($config) || !isset($config['host']) || !isset($config['secret_key']) || !isset($config['account'])) {
            exit('Service configuration error');
        }
        static $instance = [];
        if (!isset($instance[$config['host']])) {
            $instance[$config['host']] = new self($config);
        }

        return $instance[$config['host']];
    }

    /**
     * 设置属性isPhinx.
     *
     * @param string $module
     *
     * @return self
     */
    public function setIsPhinx($isPhinx)
    {
        $this->isPhinx = $isPhinx;

        return $this;
    }

    /**
     * 设置调用服务的模块 命名空间+类名 如 Store\Model\TestModel.
     *
     * @param string $module
     *
     * @return self
     */
    public function module($module)
    {
        $this->module = $module;

        return $this;
    }

    /**
     * 设置调用服务的方法名.
     *
     * @param string $method
     *
     * @return self
     */
    public function method($method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * 设置调用服务的参数.
     *
     * @param array $args
     *
     * @return self
     */
    public function args(array $args)
    {
        $this->args = $args;

        return $this;
    }

    /**
     * 是否加密传输 默认加密.
     *
     * @param bool $encrypt
     *
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
            return ['status' => 400, 'info' => 'must be set service module', 'retval' => ''];
        }
        if (empty($this->method)) {
            return ['status' => 400, 'info' => 'must be set service method', 'retval' => ''];
        }
        $args = [
            'service'  => $this->module.'::'.$this->method,
            'args'     => $this->args,
            'time'     => time(),
            'new_args' => $this->newArgs, ];
        $randomkeys = $this->randomkeys(6); // 6位干扰码
        $iv = $this->randomkeys(8); // 8位向量
        // data => 8位向量 + 加密数据 + 6位干扰码 => strrev 反转字符串
        if ($this->encrypt) {
            $this->desCrypt->setIv($iv); // 动态设置加密向量
            $args = ['data' => strrev($iv.$this->desCrypt->encrypt(json_encode($args)).$randomkeys)];
            $curl->headers['Transmission-Mode'] = 'Security';
        }
        $this->accountDesCrypt->setIv($iv); // 动态设置加密向量
        $accountData = json_encode(['account' => $this->config['account'], 'secret_key' => $this->config['secret_key']]);
        $curl->headers['Transmission-From'] = strrev($iv.$this->accountDesCrypt->encrypt($accountData).$randomkeys);
        $curl->headers['Data-Type'] = $this->dataType; // 返回数据格式
        $curl->post($this->config['host'], $args);
        $httpCode = $curl->curlGetInfo('http_code');
        $content = $curl->response()['body'];
        $header = $curl->response()['header'];
        if ('200' != $httpCode) {
            return ['status' => 500, 'info' => APP_DEBUG ? $content : '系统繁忙，程序猿正在玩命优化哦!', 'retval' => ''];
        }
        if ($this->encrypt && false === stripos($header, 'Encrypt: false')) {
            // 采用加密传输方式 则解码请求返回响应信息
            $content = $this->desCrypt->decrypt($content);
            if (!$content) {
                return ['status' => 513, 'info' => 'decryption failure :'.PHP_EOL.$curl->response()['body'], 'retval' => ''];
            }
        }
        $data = $this->isPhinx ? \Swallow\Toolkit\Util\Json::decode2($content) : json_decode($content, true);
        if (!$data) {
            return ['status' => 513, 'info' => 'json cannot be decoded : '.PHP_EOL.$content, 'retval' => ''];
        }
        $this->clear();

        return $data;
    }

    /**
     * 设置新参数.
     *
     * @param array $args 参数
     *
     * @author zengzhihao<zengzhihao@eelly.net>
     *
     * @since  2015年12月10日
     */
    public function setNewArgs($args)
    {
        $nswArgs = $this->newArgs;
        if (!empty($args)) {
            foreach ($args as $key => $arg) {
                $nswArgs[$key] = $arg;
            }
        }
        $this->newArgs = $nswArgs;

        return $this;
    }

    /**
     * 生成长度为$length的随机字符串.
     *
     * @param int $length
     *
     * @return string
     */
    private function randomkeys($length)
    {
        $returnStr = '';
        $pattern = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLOMNOPQRSTUVWXYZ';
        for ($i = 0; $i < $length; $i++) {
            $returnStr .= $pattern[random_int(0, 62)]; // 生成php随机数
        }

        return $returnStr;
    }

    /**
     * 清理参数.
     */
    private function clear(): void
    {
        $this->encrypt = true;
        unset($this->module, $this->method, $this->args);
    }
}
