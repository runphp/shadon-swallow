<?php
/*
 * PHP version 5.5
 *
 * @copyright Copyright (c) 2012-2017 EELLY Inc. (http://www.eelly.com)
 * @link      http://www.eelly.com
 * @license   衣联网版权所有
 */

namespace Swallow\ThirdParty\Easemob;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Swallow\Core\Conf;

/**
 * 环信管理器
 * 使用示例：.
 *
 * ```
 * // 聊天文件服务
 * $chatFilesService = Manager::chatFilesService();
 * $chatFilesService->uploadFile($filePath);
 * // 消息服务
 * $msgService = Manager::msgService()
 * $msgService->sendImage($from, $to, $filePath);
 * // 用户服务
 * $userService = Manager::userService();
 * $userService->addBlockUsers('xiaoming2', 'xiaoming3');
 * ```
 *
 * @method static \Swallow\ThirdParty\Easemob\Service\ChatFilesService chatFilesService()
 * @method static \Swallow\ThirdParty\Easemob\Service\MsgService msgService()
 * @method static \Swallow\ThirdParty\Easemob\Service\UserService userService()
 *
 * @author hehui<hehui@eelly.net>
 *
 * @since 2016年9月30日
 *
 * @version 1.0
 */
class Manager
{
    /**
     * 连接异常时最大重试次数.
     *
     * @var int
     */
    const  CONNECTEXCEPTION_MAXTRYTIMES = 3;

    private static $currManager;

    private $clientId;

    private $clientSecret;

    private $orgName;

    private $appName;

    private $url;

    /**
     * @var \Swallow\Cache\StaticCacheInterface
     */
    private $cacheClazz;

    private $cacheKey;

    /**
     * @var array 和环信约定的key
     */
    private $securityOptions;

    /**
     * 初始化参数.
     *
     * @param array  $options
     * @param string $options['client_id']
     * @param string $options['client_secret']
     * @param string $options['org_name']
     * @param string $options['app_name']
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since  2016年10月7日
     */
    public function __construct($options)
    {
        if (null === $options) {
            // 读取默认配置信息
            $options = Conf::get('easemob');
        }
        $this->securityOptions = $options['security'];
        $easemobOptions = $options['options'];
        $this->clientId = $easemobOptions['client_id'];
        $this->clientSecret = $easemobOptions['client_secret'];
        $this->orgName = $easemobOptions['org_name'];
        $this->appName = $easemobOptions['app_name'];
        $this->cacheClazz = $easemobOptions['cache'];
        $this->cacheKey = 'access_token_'.md5(serialize($options));
        $this->url = 'https://a1.easemob.com/'.$this->orgName.'/'.$this->appName.'/';
    }

    public static function __callStatic($method, $arguments)
    {
        static $serviceArr = [];
        self::$currManager = self::$currManager ?: self::newInstance();
        $serviceHash = $method.spl_object_hash(self::$currManager);
        if (!isset($serviceArr[$serviceHash])) {
            $clazz = __NAMESPACE__.'\\Service\\'.ucfirst($method);
            $serviceArr[$serviceHash] = new $clazz();
            $serviceArr[$serviceHash]->setManager(self::$currManager);
        }

        return $serviceArr[$serviceHash];
    }

    /**
     * 使用 APP 的 client_id 和 client_secret 获取授权管理员 token.
     *
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since 2016年10月7日
     */
    public function getToken()
    {
        static $tokenArr = [];
        $tokenKey = spl_object_hash($this);
        if (isset($tokenArr[$tokenKey])) {
            return $tokenArr[$tokenKey];
        }
        $cacheClazz = $this->cacheClazz;
        $cacheToken = $cacheClazz::get($this->cacheKey);
        if (null !== $cacheToken) {
            return $tokenArr[$tokenKey] = $cacheToken;
        }
        $body = [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ];
        $result = $this->request('token', 'POST', $body, [], [], false);
        // 有效期提前10秒
        $cacheClazz::set($this->cacheKey, $result['access_token'], $result['expires_in'] - 10);

        return $tokenArr[$tokenKey] = $result['access_token'];
    }

    /**
     * REST 请求
     *
     * @param string $service         服务名
     * @param string $method
     * @param array  $body
     * @param array  $header
     * @param array  $multipart
     * @param bool   $needToken       是否需要token
     * @param string $fileClientClazz 文件系统客户端类(如果存在则把数据存储进去,返回文件位置)
     * @param mixed  $fileExt
     *
     * @throws ConnectException|RequestException
     *
     * @return array|string
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since 2016年10月1日
     */
    public function request(
        $service,
        $method,
        array $body = [],
        array $header = [],
        array $multipart = [],
        $needToken = true,
        $fileClientClazz = null,
        $fileExt = '')
    {
        $client = $this->getServiceClient();
        if (empty($header)) {
            $header['Content-Type'] = 'application/json';
        }
        if ($needToken) {
            $header['Authorization'] = 'Bearer '.$this->getToken();
        }
        $options = [
            'body' => json_encode($body),
            'headers' => $header,
        ];
        if (!empty($multipart)) {
            $options['multipart'] = $multipart;
        }
        if (null !== $fileClientClazz) {
            $options['save_to'] = tempnam(sys_get_temp_dir(), 'fastdfs_tmp_');
        }
        if (defined('GUZZLE_DEBUG') && GUZZLE_DEBUG) {
            $options['debug'] = true;
        }
        $tryTimes = self::CONNECTEXCEPTION_MAXTRYTIMES;
        while (true) {
            try {
                $response = $client->request($method, $service, $options);
            } catch (ConnectException $e) {
                if (0 > --$tryTimes) {
                    throw $e;
                } else {
                    continue;
                }
            } catch (RequestException $e) {
                throw Exception::create($e->getRequest(), $e->getResponse(), $e);
            }
            break;
        }
        if (isset($options['save_to'])) {
            $filePath = $fileClientClazz::uploadFile($options['save_to'], $fileExt);
            @unlink($options['save_to']);

            return $filePath;
        }

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * 切换新的环信管理器.
     *
     * @param array $options
     * @param $options['client_id']
     * @param $options['org_name']
     * @param $options['app_name']
     */
    public static function newInstance($options = null, ServiceInterface $service = null)
    {
        static $instanceArr = [];
        static $serviceArr = [];
        $key = md5(serialize($options));
        if (!isset($instanceArr[$key])) {
            $instanceArr[$key] = new self($options);
        }
        if (null !== $service) {
            $serviceArr[spl_object_hash($service)] = $service;
        }
        foreach ($serviceArr as $serviceItem) {
            $serviceItem->setManager($instanceArr[$key]);
        }

        return self::$currManager = $instanceArr[$key];
    }

    /**
     * @param string $service
     *
     * @return \GuzzleHttp\Client
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since  2017年3月21日
     */
    public function getServiceClient($service = '')
    {
        static $clientArr = [];
        $serviceKey = $service.spl_object_hash(self::$currManager);
        if (!isset($clientArr[$serviceKey])) {
            $clientArr[$serviceKey] = new GuzzleClient([
                'base_uri' => $this->url.$service,
            ]);
        }

        return $clientArr[$serviceKey];
    }

    /**
     * @return array
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since  2017年3月21日
     */
    public function getSecurityOptions()
    {
        return $this->securityOptions;
    }
}
