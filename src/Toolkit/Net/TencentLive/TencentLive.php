<?php

declare(strict_types=1);

/*
 *
 * (c) eelly.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swallow\Toolkit\Net\TencentLive;

use GuzzleHttp\Client;
use function GuzzleHttp\Psr7\build_query;
use GuzzleHttp\Psr7\Response;

/**
 * 腾讯云直播码模式
 *
 * @author wangjiang<wangjiang@eelly.net>
 */
class TencentLive
{
    protected static $instance = null;

    protected $apiKey;

    protected $appId;

    protected $baseUrl = 'http://fcgi.video.qcloud.com/common_access';

    protected $client = null;

    protected $ttl = 300;

    protected $requestUrl = '';

    public static function getInstance(array $config)
    {
        if (!self::$instance instanceof self){
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    private function __construct(array $config)
    {
        if (empty($config)){
            throw new \ErrorException('TencentLive config cannot be empty');
        }

        $this->apiKey = isset($config['apiKey']) ? $config['apiKey'] : '0af800050da81fbbb196f77cf5b6b5b9';
        $this->appId   = $config['appId'];
        $this->client = new Client([
            'timeout' => 30,
        ]);
    }

    /**
     * 发送请求
     *
     * @param string $method 请求方式get/post
     * @param string $interface 请求接口名称
     * @param array $args 请求接口参数
     * @throws \ErrorException
     * @return mixed|string
     */
    public function request(string $method, string $interface, array $args)
    {
        $this->setRequestUrl($interface);
        $methodName = $method . 'Request';
        if (!method_exists($this, $methodName)){
            throw new \ErrorException($methodName . ' doesn\'t exist');
        }

        /** @var Response $response */
        $response = $this->$methodName($args);
//         dump($this->requestUrl);
        if ('200' != $response->getStatusCode()){
            $errorMessage = sprintf('[statusCode] %s,[reasonPhrase] %s,[errorInfo] %s',
                    $response->getStatusCode(),
                    $response->getReasonPhrase(),
                    $response->getBody()->getContents()
                );
            throw new \ErrorException($errorMessage);
        }
        $contents = $response->getBody()->getContents();

        return 0 === strpos($contents, '{') ? json_decode($contents, true) : $contents;
    }

    /**
     * 发送GET请求
     *
     * @param array $args 请求参数
     * @return Response
     */
    public function getRequest(array $args)
    {
        $this->requestUrl .= '&' . build_query($args);
        $response = $this->client->request('GET', $this->requestUrl);

        return $response;
    }

    /**
     * 发送POST请求
     *
     * @param array $args 请求参数
     * @return Response
     */
    public function postRequest(array $args)
    {
        $response = $this->client->request('POST', $this->requestUrl, [
            'json' => $args,
        ]);

        return $response;
    }

    /**
     *@Validation(
     *   @Url(0,{message:"非法的url",allowEmpty:true})
     * )
     */
    public function setBaseUrl(string $url = '')
    {
        !empty($url) && $this->baseUrl = $url;
    }

    /**
     * 设置请求url
     *
     * @param string $interface 请求接口名称
     */
    public function setRequestUrl(string $interface)
    {
        $curTime = time() + $this->ttl;
        $sign = md5(sprintf('%s%s',
            $this->apiKey,
            $curTime
            ));
        $args['appid']      = $this->appId;
        $args['cmd']      = $this->appId;
        $args['interface']  = $interface;
        $args['sign']       = $sign;
        $args['t']          = $curTime;

        $this->requestUrl = $this->baseUrl . '?' .build_query($args);
    }
}