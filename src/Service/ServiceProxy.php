<?php
/*
 * PHP version 5.5
 *
 * @copyright Copyright (c) 2012-2017 EELLY Inc. (http://www.eelly.com)
 * @link      http://www.eelly.com
 * @license   衣联网版权所有
 */

namespace Swallow\Service;

use GuzzleHttp\ClientInterface;
use Swallow\Core\Log;
use Swallow\Core\Reflection;
use Swallow\Toolkit\Encrypt\DesCrypt;

/**
 * 代理服务层
 *
 * 用于跨网络调用
 *
 * @author    hehui<hehui@eelly.net>
 *
 * @since     2017年3月29日
 *
 * @version   1.0
 */
class ServiceProxy
{
    /**
     * @var string
     */
    private $clientName = 'ios';

    /**
     * @var int
     */
    private $clientVersion = 440;

    /**
     * @var string
     */
    private $clientUserType = 'buyer';

    /**
     * @var string
     */
    private $app = 'Mall';

    /**
     * @var string
     */
    private $deviceNumber;

    /**
     * login token.
     *
     * @var string
     */
    private $loginToken;

    /**
     * access token.
     *
     * @var string
     */
    private $accessToken;

    /**
     * service name.
     *
     * @var string
     */
    private $serviceName;

    /**
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * @var string
     */
    private $descryptKey;

    /**
     * @var DesCrypt
     */
    private $desCrypt;

    /**
     * @var array
     */
    private $headers = ['Transmission-Mode' => 'Security'];

    /**
     * @var int
     */
    private $maxRetryTimes = 86400;

    public function __construct($httpClient, $serviceName, $config, $options)
    {
        $this->httpClient = $httpClient;
        $this->serviceName = $serviceName;
        $this->deviceNumber = str_replace('.', '', uniqid('php_', true));
        if (isset($options['clientName'])) {
            $this->descryptKey = $config[$options['clientName']]['encoding_aes_key'];
        } else {
            $this->descryptKey = $config[$this->clientName]['encoding_aes_key'];
        }
        foreach ($options as $key => $value) {
            $this->$key = is_array($this->$key) ? array_merge($this->$key, $value) : $value;
        }
        $this->headers['Transmission-From'] = $this->accessToken;
        $this->desCrypt = new DesCrypt($this->descryptKey);
    }

    public function __call($method, $args)
    {
        $retryTimes = 0;
        $startTime = milliseconds();
        while (1) {
            $options = $this->prepareOptions($method, $args);
            try {
                $response = $this->httpClient->request('post', null, $options);
            } catch (\GuzzleHttp\Exception\ServerException $e) {
                Log::error($e->getMessage(), [
                    'options' => $options,
                ]);

                return;
            }
            $contents = $response->getBody()->getContents();
            $contentsArr = json_decode($contents, true);
            // 目前不等于200的都是异常
            ++$retryTimes;
            $status = (int) $contentsArr['status'];
            if (200 == $status || (700 <= $status && 800 > $status)) {
                break;
            } else {
                Log::error("[c=red]server error $retryTimes times[/c]", [
                    'contents' => is_array($contentsArr) ? $contentsArr : $contents,
                    'options' => $options,
                ]);
                if ($this->maxRetryTimes <= $retryTimes) {
                    break;
                }
                continue;
            }
        }
        $contentsArr = $this->desCrypt->decrypt($contentsArr['retval']['data']);
        $contentsArr = json_decode($contentsArr, true);
        Log::info(sprintf('服务调用成功, 尝试[c=yellow]%s[/c]次, 使用[c=yellow]%s[/c]毫秒', $retryTimes, milliseconds() - $startTime), [
            'class' => $this->serviceName,
            'method' => $method,
            'params' => (array) $args,
            'return' => $contentsArr,
        ]);

        return $contentsArr;
    }

    private function prepareOptions($method, $args)
    {
        $formParams = [
            'client_name' => $this->clientName,
            'method' => $method,
            'service_name' => $this->serviceName,
            'time' => time(),
            'client_version' => $this->clientVersion,
            'client_user_type' => $this->clientUserType,
            'user_login_token' => $this->loginToken,
            'app' => $this->app,
            'device_number' => $this->deviceNumber,
        ];
        $reflectionMethod = Reflection::getMethod($this->serviceName, $method);
        $paramters = [];
        /* @var \ReflectionParameter $reflectionParameter */
        foreach ($reflectionMethod->getParameters() as $reflectionParameter) {
            $position = $reflectionParameter->getPosition();
            if (isset($args[$position])) {
                $value = $args[$position];
            } elseif ($reflectionParameter->isDefaultValueAvailable()) {
                $value = $reflectionParameter->getDefaultValue();
            }
            if (isset($value)) {
                $paramters[$reflectionParameter->getName()] = $value;
            }
        }
        $formParams['args'] = json_encode($paramters);
        $formParams = json_encode($formParams);
        $desCrypt = $this->desCrypt;
        $iv = '12345678';
        $randomkeys = '123456';
        $desCrypt->setIv($iv); // 动态设置加密向量
        $formParams = strrev($iv.$desCrypt->encrypt($formParams).$randomkeys);
        $formParams = [
            'data' => $formParams,
        ];
        $options = [
            'headers' => $this->headers,
            'form_params' => $formParams,
            'verify' => false,
        ];

        return $options;
    }
}
