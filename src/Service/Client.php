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

namespace Swallow\Service;

use Phalcon\Di;
use Swallow\Exception\StatusCode;

class Client
{
    /**
     * @var $client
     */
    private static $client;

    /**
     * @var $di
     */
    private static $di;

    /**
     * @var $serviceUrl
     */
    private static $serviceUrl;

    /**
     * @var $serviceProtocol
     */
    private static $serviceProtocol;

    /**
     * @var $protocol
     */
    private static $tcp = 'tcp';

    /**
     * __construct.
     *
     * @return \Swallow\Service\Client
     *
     * @author 范世军<fanshijun@eelly.net>
     *
     * @since  2015年11月16日
     */
    public function __construct()
    {
        self::$serviceProtocol = self::$di->getConfig()->serviceProtocol;
        if (!self::$client) {
            if (self::$serviceProtocol == self::$tcp) {
                self::$client = \Swallow\Swoole\Client::getInstance(self::$serviceUrl);
            } else {
                self::$client = new \GuzzleHttp\Client();
            }
        }
    }

    /**
     * @param string $method 方法
     * @param array  $args   参数
     */
    public function __call($method, $args)
    {
        $className = static::class;

        return $this->assemble($className, $method, $args);
    }

    /**
     * @param string $isNewInstance
     *
     * @return self
     * @return \Phalcon\mixed
     *
     * @author 范世军<fanshijun@eelly.net>
     *
     * @since  2015年11月16日
     */
    public static function getInstance($isNewInstance = false)
    {
        self::$di = $di = Di::getDefault();
        $className = static::class;
        $classArr = explode('\\', $className);
        $moduleName = $classArr[0];
        $modulesService = self::$di->getConfig()->modulesService->toArray();
        $key = array_rand($modulesService[$moduleName]);
        self::$serviceUrl = $modulesService[$moduleName][$key];
        $service = (false === $isNewInstance) ? $di->getShared($className) : $di->get($className);
        if (APP_DEBUG) {
            $verify = $di->getShared('\Swallow\Debug\VerifyBack');
            $verify->callClass($className);
        }

        return $service;
    }

    /**
     * 返回处理.
     *
     * @param $className
     * @param $method
     * @param $args
     *
     * @return array
     *
     * @author 范世军<fanshijun@eelly.net>
     *
     * @since  2015年10月26日
     */
    public function assemble($className, $method, $args)
    {
        $params = ['service' => $className.'::'.$method, 'args' => json_encode($args), 'time' => time()];
        $modulesCrypt = self::$di->getConfig()->modulesCrypt;
        if (self::$serviceProtocol == self::$tcp) {
            if (true == $modulesCrypt) {
                $appKey = self::$di->getConfig()->appKey;
                $this->desCrypt = new \Swallow\Toolkit\Encrypt\DesCrypt($appKey);
                $params = ['Transmission-Mode' => 'Security', 'Transmission-From' => 'Module', 'data' => $this->encryptData(json_encode($params))];
            }
            $retval = self::$client->call($params);
            if (true == $modulesCrypt) {
                $retval = $this->desCrypt->decrypt($retval);
            }
            $retval = json_decode($retval, true);
        } else {
            $headers = [];
            if (true == $modulesCrypt) {
                $appKey = self::$di->getConfig()->appKey;
                $this->desCrypt = new \Swallow\Toolkit\Encrypt\DesCrypt($appKey);
                $params = ['data' => $this->encryptData(json_encode($params))];
                $headers['Transmission-Mode'] = 'Security';
            }
            $headers['Transmission-From'] = 'Module';
            $headers['Data-Type'] = 'json';
            $res = self::$client->request('POST', 'https://'.self::$serviceUrl.'/service.php', ['headers' => $headers, 'form_params' => $params]);
            $data = $res->getBody();
            if (true == $modulesCrypt) {
                $data = $this->desCrypt->decrypt($data);
            }
            $retval = json_decode($data, true);
        }
        if (!isset($retval['status'])) {
            $retval = ['status' => StatusCode::OK, 'info' => '', 'retval' => null];
        }

        return $retval;
    }

    /**
     * 加密数据.
     *
     * @param string $data
     *
     * @return string
     */
    private function encryptData($data)
    {
        $randomkeys = $this->randomkeys(6); // 6位干扰码
        $iv = $this->randomkeys(8); // 8位向量
        $this->desCrypt->setIv($iv); // 动态设置加密向量
        $encryptData = strrev($iv.$this->desCrypt->encrypt($data).$randomkeys);

        return $encryptData;
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
}
