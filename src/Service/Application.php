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

use Eelly\SDK\EellyClient;
use Eelly\SDK\Oauth\Api\TokenConvert;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Shadon\Logger\Handler\DingDingHandler;
use Swallow\Exception\LogicException;
use Swallow\Exception\StatusCode;
use Swallow\Exception\SystemException;
use Swallow\Toolkit\Util\Arrays;
use Whoops\Exception\Inspector;
use Whoops\Handler\PlainTextHandler;

/**
 * service app.
 *
 * @author    何辉<hehui@eely.net>
 *
 * @since     2015年9月11日
 *
 * @version   1.0
 */
class Application extends \Phalcon\Mvc\Application implements \Swallow\Bootstrap\ApiStatisticsInterface
{
    /**
     * token信息.
     *
     * @var array
     */
    private static $tokenConfig = [];

    /**
     * 请求有效时间 30s有效.
     *
     * @var int
     */
    private static $expirationTime = 30;

    /**
     * 加密实例.
     *
     * @var string
     */
    private $desCrypt = null;

    /**
     * 是否加密传输 默认加密.
     *
     * @var bool
     */
    private $encrypt = false;

    /**
     * 时间戳.
     *
     * @var string
     */
    private $timeStamp = '';

    /**
     * 签名密钥.
     *
     * @var string
     */
    private $secret = '';

    /**
     * 客户端.
     *
     * @var string
     */
    private $client = '';

    /**
     * 是否请求token.
     *
     * @var bool
     */
    private $isToGetToken = false;

    /**
     * token加解密密钥.
     *
     * @var string
     */
    private $tokenSecret = '%HdoQqwI3sQ3bBnaLReX^hMp';

    /**
     * 请求参数.
     *
     * @var array
     */
    private $requestParam = [];

    /**
     * 用户登录信息.
     *
     * @var array
     */
    private $userLoginInfo = [];

    /**
     *调试信息.
     *
     * @var string
     */
    private $debugInfo = '';

    /**
     *系统时间.
     *
     * @var int
     */
    private $sysTimeStamp = '';

    /**
     * 是否【手机端】且【凤凰或以后的版本】.
     */
    private $isPhinx = false;

    /**
     * 加密版本  （空为默认加密方式   v2:3des加密后再base64加密）.
     */
    private $encryptVersion = '';

    /**
     * 原始请求数据.
     *
     * @var string
     */
    private $requestData;

    /**
     * 解密请求数据.
     *
     * @var string
     */
    private $requestDataDecrypt;

    /**
     * 系统名.
     *
     * @var string
     */
    private $app;

    /**
     * 服务类名.
     *
     * @var string
     */
    private $serviceName;

    /**
     * 服务方法名.
     *
     * @var string
     */
    private $method;

    /**
     * 方法参数.
     *
     * @var string
     */
    private $args;

    /**
     * 客户端用户名。参考[这里](http://servicemanage.eelly.test/common/account).
     *
     * @var string
     */
    private $clientName;

    /**
     * 客户端版本.
     *
     * @var string
     */
    private $clientVersion;

    /**
     * 客户端类型。seller, buyer.
     *
     * @var string
     */
    private $clientUserType;

    /**
     * 客户端设备ID。针对移动端，可为空.
     *
     * @var string
     */
    private $deviceNumber;

    /**
     * 接口的access_token.
     *
     * @var string
     */
    private $transmissionFrom;

    /**
     * 终端用户（个人账号）登录的token.
     *
     * @var string
     */
    private $userLoginToken;

    /**
     * user_login_token对应的user_id.
     *
     * @var string
     */
    private $userLoginTokenUserId;

    /**
     * 处理结果状态码
     *
     * @var string
     */
    private $handleStatus;

    /**
     * 处理结果提示.
     *
     * @var string
     */
    private $handleInfo;

    /**
     * 处理结果数据.
     *
     * @var array
     */
    private $handleRetval;

    /**
     * 数据模式.
     *
     * @var string
     */
    private $transmissionMode;

    /**
     * 客户端ip地址
     *
     * @var string
     */
    private $clientAddress;

    /**
     * sessionId.
     *
     * @var string
     */
    private $sessionId;

    /**
     * 是否移除加密.
     *
     * @var bool
     */
    private $isRemoveEncrypt = false;

    /**
     * 注册标准模块.
     *
     * 自动把模块名转为下面格式
     * <code>
     * [
     *   'className' => 'Name\Module',
     *   'path' => 'application/name/src/Name/Module.php'
     * ]
     * </code>
     *
     * @param array $modules
     * @param bool  $merge
     *
     * @return \Phalcon\Mvc\Application
     *
     * @author 何辉<hehui@eely.net>
     *
     * @since  2015年9月6日
     */
    public function registerStandardModules(array $modules, $merge = false)
    {
        $loader = $this->getDI()->getLoader();
        $completeModules = [];
        $clazzes = [];
        $this->sysTimeStamp = time();
        foreach ($modules as $name) {
            $ucfirstName = ucfirst($name);
            $completeModules[$name] = [
                'className' => $ucfirstName.'\Module',
                'path'      => "application/$name/src/$ucfirstName/Module.php", ];
            $clazzes[$completeModules[$name]['className']] = $completeModules[$name]['path'];
        }
        $loader->registerClasses($clazzes)->register();

        return $this->registerModules($completeModules, $merge);
    }

    public function bootstrap()
    {
        $eventsManager = $this->getEventsManager();
        if (is_object($eventsManager)) {
            $eventsManager->fire('application:beforeBootstrap', $this);
        }
        $data = $this->handle();
        //日志记录
        $this->logging($data);
        if ($this->isTestVerify()) {
            return json_encode($data);
        }
        $retval = '';
        if (200 == $data['status']) {
            $this->isPhinx || $data['retval'] = Arrays::toString($data['retval']);
            $retval = $data['retval'];
            if (!empty($this->encryptVersion) && 'v2' == $this->encryptVersion) {
                $signData = $data;
                ksort($signData);
                $checkSign = md5(json_encode($signData).$this->isToGetToken);
            } elseif ($this->encrypt && null != $this->desCrypt) {
                $retval = json_encode($retval);
                $retval = $this->desCrypt->encrypt($retval);
                !empty($this->encryptVersion) && 'v2' == $this->encryptVersion && $retval = base64_encode($retval);
            }
            //生成签名
            //$signature = $this->signature(json_encode($retval));
        }
        $data['retval'] = ['data' => $retval, 'signature' => 'unsupport'];
        if (!$this->isRemoveEncrypt && APPLICATION_ENV != 'prod' && 0 == time() % 3) {
            $data = ['status' => 400, 'retval'=>null, 'info' => '接口请求升级中(去除加解密)'];
        }
        if (is_object($eventsManager)) {
            $eventsManager->fire('application:afterBootstrap', $this);
        }

        return json_encode($data);
    }

    public function handle($arguments = null)
    {
        $retval = ['status' => StatusCode::OK, 'info' => '', 'retval' => null];
        try {
            $eventsManager = $this->getEventsManager();
            if (is_object($eventsManager)) {
                if (false === $eventsManager->fire('application:boot', $this)) {
                    throw new \ErrorException('application:boot error');
                }
            }
            $defaultDi = $this->getDI();
            /* @var \Phalcon\Http\Request $request */
            $request = $defaultDi->getRequest();

            $this->transmissionMode = $request->getHeader('Transmission-Mode')
                ? $request->getHeader('Transmission-Mode')
                : $request->get('Transmission-Mode');
            $this->transmissionFrom =
                $request->getHeader('Transmission-From')
                    ? $request->getHeader('Transmission-From')
                    : $request->get('Transmission-From');
            $transmissionToken = $request->getHeader('Transmission-Token');
            $transmissionVersion = $request->getHeader('Transmission-Version');
            $this->encryptVersion = empty($transmissionVersion) ? '' : $transmissionVersion;
            $this->isToGetToken = $transmissionToken ? $transmissionToken : $request->get('Transmission-Token');
            $isOld = false;
            $option = [];

            if (!$this->isToGetToken && !$this->isTestVerify()) {
                //验证access_token
                $res = \Api\Logic\CredentialLogic::getInstance()->verifyAccessToken($this->transmissionFrom);

                self::$tokenConfig = $res['data'];
            }
            if ('Security' == $this->transmissionMode && 'v2' == $transmissionVersion) {
                $content = file_get_contents('php://input');
                $this->requestData = $this->requestDataDecrypt = json_decode($content, true);
                $this->requestDataDecrypt['args'] = $this->requestData['args'] = json_encode($this->requestDataDecrypt['args']);
                if (false == $this->verifyV2Param($this->requestData)) {
                    $this->debugInfo = '验证verify == false,解码data有问题';
                    throw new LogicException('Request parameter error, or decryption failure!', StatusCode::SERVICE_BAD_REQUEST);
                }
                if (false == $this->verifyV2Sign($this->requestData)) {
                    $this->debugInfo = '签名verify == false,解码data有问题';
                    throw new LogicException('Request Sign error', StatusCode::SERVICE_BAD_REQUEST);
                }
                $this->encrypt = false;
            } elseif ('Security' == $this->transmissionMode) {
                // 如果启用加密安全传输
                $this->requestData = 'Security' == $this->transmissionMode ? $request->get('data') : $request->getPost('data');
                $this->requestDataDecrypt = $this->decode($this->requestData, $this->transmissionFrom, $transmissionVersion); // 解码
                $verify = $this->verifyParam($this->requestDataDecrypt); // 验证
                if (false == $verify) {
                    $this->debugInfo = '验证verify == false,解码data有问题';
                    throw new LogicException('Request parameter error, or decryption failure!', StatusCode::SERVICE_BAD_REQUEST);
                }
                $this->encrypt = true;
            } else {
                if (APP_DEBUG) {
                    $this->requestData = $this->requestDataDecrypt =
                        $request->getPost() ? $request->getPost() : $request->get();
                    $this->encrypt = false;
                } else {
                    throw new LogicException('You do not have permission to request!', StatusCode::REQUEST_FORBIDDEN);
                }
                if (!isset($this->requestDataDecrypt['service_name'])) {
                    throw new LogicException('参数错误', StatusCode::INVALID_ARGUMENT);
                }
            }
            //转换参数
            $isMore = true;
            if (!('Base\\Service\\ClientService' == $this->requestDataDecrypt['service_name']
                && 'curlMoreReqMallService' == $this->requestDataDecrypt['method'])) {
                $isMore = false;
            }

            $this->app = $this->requestDataDecrypt['app'];
            $this->serviceName = $this->requestDataDecrypt['service_name'];
            $this->method = $this->requestDataDecrypt['method'];
            $this->args = (isset($this->requestDataDecrypt['args']) && 'null' != $this->requestDataDecrypt['args'])
                ? $this->requestDataDecrypt['args']
                : null;
            //$this->timeStamp = $this->requestDataDecrypt['time'];
            $version = isset($this->requestDataDecrypt['version']) ? $this->requestDataDecrypt['version'] : '';
            $this->client = isset($this->requestDataDecrypt['client']) ? $this->requestDataDecrypt['client'] : '';
            // 登陆信息
            $this->userLoginToken = $option['user_login_token'] =
                isset($this->requestDataDecrypt['user_login_token'])
                    ? $this->requestDataDecrypt['user_login_token']
                    : '';
            // 客户端信息
            $clearCache = $option['clear_cache'] =
                isset($this->requestDataDecrypt['clear_cache'])
                    ? $this->requestDataDecrypt['clear_cache']
                    : '';
            $this->clientVersion = $option['client_version'] =
                isset($this->requestDataDecrypt['client_version'])
                    ? $this->requestDataDecrypt['client_version']
                    : '';
            $this->clientName = $option['client_name'] =
                isset($this->requestDataDecrypt['client_name'])
                    ? $this->requestDataDecrypt['client_name']
                    : '';
            $this->clientUserType = $option['client_user_type'] =
                isset($this->requestDataDecrypt['client_user_type'])
                    ? $this->requestDataDecrypt['client_user_type']
                    : '';
            $this->deviceNumber = $option['device_number'] =
                isset($this->requestDataDecrypt['device_number'])
                    ? $this->requestDataDecrypt['device_number']
                    : '';
            $this->clientAddress = $option['client_address'] =
                !empty($request->getClientAddress())
                    ? $request->getClientAddress()
                    : '';
            $this->sessionId = $option['session_id'] =
                isset($this->requestDataDecrypt['session_id'])
                    ? $this->requestDataDecrypt['session_id']
                    : '';
            $this->getDI()->getShared('clientInfo')->setClearCache($clearCache)->setClientInfo([
                'client_version'   => $this->clientVersion,
                'client_name'      => $this->clientName,
                'client_user_type' => $this->clientUserType,
                'device_number'    => $this->deviceNumber,
                'client_address'   => $this->clientAddress,
                'session_id'       => $this->sessionId,
            ]);

            $this->isPhinx = in_array(strtolower($this->clientName), ['ios', 'android']) && (('buyer' == $this->clientUserType && $this->clientVersion >= 430) || ('seller' == $this->clientUserType && $this->clientVersion >= 220));

            !empty($version) && $this->method = $this->method.$version;
            if (null !== $this->args) {
                $this->args = json_decode($this->args, true);
                null === $this->args && $this->args = [];
                if (isset($this->args['clear'])) {
                    if (!empty($this->args['clear'])) {
                        $_ENV['isInternalUser'] = true;
                    }
                    unset($this->args['clear']);
                }
            } else {
                $this->args = [];
            }
            //验证登陆
            $isLogin = false;
            if ($isMore) {
                //校验参数
                $newMethods = $oldMethods = [];
                foreach ($this->args['params'] as $key => $newArg) {
                    $isOld = false;
                    if (false === strpos($newArg['module'], '\\')) {
                        //新接口
                        //$class = empty($this->client) ? $this->app . '\Service\\' . $newArg['module'] : $this->app . '\Service\\' . $this->client . '\\' .$newArg['module'];
                        //新接口校验
                        //$logicName = str_replace('\\Service\\', '\\Logic\\', $class);
                        //$logicName = preg_replace('/Service$/', 'Logic', $logicName);
                        //$this->verifyClass($class, $logicName); //验证类
                        //$this->verifyMethod($logicName, $newArg['method']); //验证方法
                        //$newMethods[$key] = $newArg;
                        //多接口调用屏蔽新接口
                        throw new LogicException('不允许调用新接口', StatusCode::SERVICE_BAD_REQUEST);
                    } else {
                        //旧接口
                        $class = $newArg['module'];
                        !empty($this->client) && $class = str_replace('\\Service\\', '\\Service\\'.$this->client.'\\', $class);
                        $isOld = true;
                        $oldMethods[$key] = $newArg;
                    }
                    $version = isset($newArg['version']) ? (int) ($newArg['version']) : '';
                    //模块调模块，不验证
                    if ('Module' != $this->transmissionFrom) {
                        //判断是否获取access_token 是则不验证
                        if (!$this->isToGetToken && !$this->isTestVerify()) {
                            //验证权限
                            $isCheckLogin = $this->verifyPermissions($this->app, $class, $newArg['method'].$version, $isOld);
                            false == $isLogin && $isLogin = $isCheckLogin;
                            $this->secret = self::$tokenConfig['token'];
                        } else {
                            $this->secret = $this->tokenSecret;
                        }
                    }
                }
            } else {
                if (false === strpos($this->serviceName, '\\')) {
                    $class = empty($this->client)
                        ? $this->app.'\Service\\'.$this->serviceName
                        : $this->app.'\Service\\'.$this->client.'\\'.$this->serviceName;
                } else {
                    $class = $this->serviceName;
                    !empty($this->client) && $class = str_replace('\\Service\\', '\\Service\\'.$this->client.'\\', $class);
                    $isOld = true;
                }
                if (!$isOld) {
                    $logicName = str_replace('\\Service\\', '\\Logic\\', $class);
                    $logicName = preg_replace('/Service$/', 'Logic', $logicName);
                    $this->verifyClass($class, $logicName); //验证类
                    $this->verifyMethod($logicName, $this->method); //验证方法
                }

                //模块调模块不验证
                if ('Module' != $this->transmissionFrom) {
                    //判断是否获取access_token 是则不验证
                    if (!$this->isToGetToken && !$this->isTestVerify() && 0 != strpos($class, 'Eelly\\SDK\\')) {
                        //验证权限
                        $isLogin = $this->verifyPermissions($this->app, $class, $this->method, $isOld);
                        $this->secret = self::$tokenConfig['token'];
                    } else {
                        $this->secret = $this->tokenSecret;
                    }
                }
            }

            // 接口系统不再校验登录，只用作日志记录
            !empty($this->userLoginToken) && $this->verifyLogin(['user_login_token' => $this->userLoginToken]);
            // 调用sdk
            if (0 === strpos($this->serviceName, 'Eelly\\SDK\\')) {
                if (!class_exists($this->serviceName) || !method_exists($this->serviceName, $this->method)) {
                    throw new LogicException("接口未找到({$this->serviceName}:{$this->method})", StatusCode::DATA_NOT_FOUND);
                }
                // iniitialize eelly client
                $redisConfig = (require 'config/'.APPLICATION_ENV.'/cache.php')['Redis'];
                $options = [
                    'parameters' => $redisConfig['default']['seeds'],
                    'options'    => ['cluster' => 'redis'],
                    'statsKey'   => '_PHCR_EELLY_STATS',
                ];
                $cache = new \Shadon\Cache\Backend\Predis(new \Phalcon\Cache\Frontend\Igbinary(), $options);
                $eellyClient = \Eelly\SDK\EellyClient::initialize(require 'config/'.APPLICATION_ENV.'/config.eellyclient.php', $cache);
                if ($this->userLoginInfo && isset($this->userLoginInfo['access_token'])) {
                    $cache = $this->getDI()->getShared('cache');
                    $cacheKey = __METHOD__.':'.$this->userLoginInfo['access_token'];
                    $accessToken = $cache->get($cacheKey);
                    while (!$accessToken instanceof AccessToken) {
                        static $preExcepton = null;
                        try {
                            $accessToken = (new TokenConvert())->newMallLogin($this->userLoginInfo['access_token']);
                            $accessToken = new AccessToken($accessToken);
                            $cache->save($cacheKey, $accessToken, $accessToken->getExpires());
                        } catch (\Eelly\Exception\LogicException $e) {
                            if (null === $preExcepton) {
                                (new TokenConvert())->saveNewMallAccessToken($this->userLoginInfo['access_token'], ['uid' => $this->userLoginInfo['uid']]);
                                $preExcepton = $e;
                            } else {
                                throw new LogicException('请重新登录', StatusCode::DATA_NOT_FOUND, ['uid' => $this->userLoginInfo['uid']]);
                            }
                        }
                    }
                    if ($accessToken->hasExpired()) {
                        try {
                            $accessToken = $eellyClient->getSdkClient()->getProvider()->getAccessToken(
                                'refresh_token',
                                ['refresh_token' => $accessToken->getRefreshToken()]
                            );
                            $cache->save($cacheKey, $accessToken, $accessToken->getExpires());
                        } catch (IdentityProviderException $e) {
                            $cache->delete($cacheKey);
                            throw new LogicException('请重试', StatusCode::OVER_FLOW);
                        }
                    }
                    $eellyClient->getSdkClient()->setAccessToken($accessToken);
                }
                $sdk = new $this->serviceName();
                $reflectionClass = new \ReflectionClass($this->serviceName);
                $parameters = $reflectionClass->getMethod($this->method)->getParameters();
                $argsNew = [];
                if (!empty($this->args) && !empty($parameters)) {
                    foreach ($parameters as $val) {
                        if (isset($this->args[$val->name])) {
                            $argsNew[] = $this->args[$val->name];
                        } elseif ($val->isDefaultValueAvailable()) {
                            $argsNew[] = $val->getDefaultValue();
                        } else {
                            // 非可选参数
                            if (!$val->isOptional()) {
                                throw new LogicException('参数错误', StatusCode::INVALID_ARGUMENT);
                            }
                        }
                    }
                }

                $res = call_user_func_array([$sdk, $this->method], $argsNew);
            } elseif ($isOld) {
                // 过渡版本 : android和ios客户端，厂+版本2.2.0，店+版本4.3.0之前的版本
                $isTransition = in_array(strtolower($this->clientName), ['ios', 'android']) && (('buyer' == $this->clientUserType && $this->clientVersion < 430) || ('seller' == $this->clientUserType && $this->clientVersion < 220));
                $service = $isTransition ? 'transitionService' : 'oldService';
                $config = $this->getDI()->getConfig();
                $serviceOption = $config->$service->toArray();
                // 第二代接口 start
                $httpClient = new \GuzzleHttp\Client([
                    'http_errors' => false,
                    'verify'      => APPLICATION_ENV == 'prod',
                ]);
                $arr = explode('\\', $this->serviceName);
                $url = $config->url->mall.'/service.php?_url=';
                if (3 == count($arr)) {
                    $url .= sprintf(
                        '/%s/%s/%s',
                        lcfirst($arr[0]),
                        lcfirst(substr($arr[2], 0, strlen($arr[2]) - 7)),
                        $this->method
                    );
                } elseif (4 == count($arr)) {
                    $url .= sprintf(
                        '/%s_%s/%s/%s',
                        lcfirst($arr[0]),
                        lcfirst($arr[2]),
                        lcfirst(substr($arr[3], 0, strlen($arr[3]) - 7)),
                        $this->method
                    );
                } elseif (5 == count($arr)) {
                    $url .= sprintf(
                        '/%s_%s_%s/%s/%s',
                        lcfirst($arr[0]),
                        lcfirst($arr[2]),
                        lcfirst($arr[3]),
                        lcfirst(substr($arr[4], 0, strlen($arr[4]) - 7)),
                        $this->method
                    );
                } else {
                    throw new LogicException('Not found', 404);
                }
                $data = $httpClient->post($url, [
                    'headers' => [
                        'client-id'        => $serviceOption['account'],
                        'client-secret'    => $serviceOption['secret_key'],
                        'login-token'      => $this->userLoginToken,
                        'clear-cache'      => $clearCache,
                        'client-version'   => $this->clientVersion,
                        'client-name'      => $this->clientName,
                        'client-user-type' => $this->clientUserType,
                        'device-number'    => $this->deviceNumber,
                        'client-address'   => $this->clientAddress,
                        'session-id'       => $this->sessionId,
                        'User-Agent'       => 'newmall/1.0 '.\GuzzleHttp\default_user_agent(),
                    ],
                    'json' => $this->args,
                ]);

                if (200 == $data->getStatusCode()) {
                    $body = (string) $data->getBody();
                    $res = $this->isPhinx ? \Swallow\Toolkit\Util\Json::decode2($body) : json_decode($body, true);
                } else {
                    $res = [
                        'status' => $data->getStatusCode(),
                        'info'   => '服务器异常(www)',
                    ];
                }
                if (!is_array($res)) {
                    $res = [
                        'status' => 500,
                        'info'   => '服务器异常(data)',
                    ];
                    $this->getLogger()->warning('Not Json', ['body' => $body]);
                }
                // 第二代接口 end
                if (StatusCode::OK == $res['status']) {
                    $res = $res['data'];
                    if (is_bool($res)) {
                        $res = ['result' => $res];
                    }
                } else {
                    throw new LogicException($res['info'], $res['status']);
                }
            } else {
                $class = $this->getDI()->getShared($logicName);
                $methodObj = \Swallow\Core\Reflection::getClass($class)->getMethod($this->method);
                $parameters = $methodObj->getParameters();
                $argsNew = [];
                if (!empty($this->args) && !empty($parameters)) {
                    foreach ($parameters as $val) {
                        if (isset($this->args[$val->name])) {
                            $argsNew[] = $this->args[$val->name];
                        } elseif ($val->isDefaultValueAvailable()) {
                            $argsNew[] = $val->getDefaultValue();
                        }
                    }
                }
                $res = call_user_func_array([$class, $this->method], $argsNew);
            }

            $retval['retval'] = $res;
        } catch (LogicException $e) {
            $retval['info'] = $e->getMessage();
            $retval['status'] = $e->getCode();
            $retval['retval'] = $e->getArgs();
        } catch (\Eelly\Exception\LogicException $e) {
            $retval['info'] = $e->getMessage();
            $retval['status'] = StatusCode::UNKNOW_ERROR;
        } catch (\Phalcon\Mvc\Model\Exception $e) {
            $retval['info'] = '系统繁忙！';
            $retval['status'] = $e->getCode();
            if (APP_DEBUG) {
                $retval['info'] .= ' detail:'.$e->getMessage().' in '.$e->getFile().':'.$e->getLine();
            }
            $retval['retval'] = null;
            $this->handleException($e);
        } catch (SystemException $e) {
            $retval['info'] = '程序内部错误';
            $retval['status'] = $e->getCode();
            $retval['retval'] = null;
            $this->handleException($e);
        } catch (\Throwable $e) {
            $retval['info'] = '服务系统错误';
            if (APP_DEBUG) {
                $retval['info'] .= ' detail:'.$e->getMessage().' in '.$e->getFile().':'.$e->getLine();
            }
            $retval['retval'] = null;
            $this->handleException($e);
        }

        return $retval;
    }

    /**
     * 生成签名.
     *
     * @param string $msg 消息
     *
     * @author zengzhihao<zengzhihao@eelly.net>
     *
     * @deprecated
     * @since  2015年12月1日
     */
    public function signature($msg)
    {
        return 'unsupport';
        //生成安全签名
        $sha1 = new \Swallow\Toolkit\Encrypt\Sha1();
        $array = $sha1->getSHA1($msg, $this->secret, $this->timeStamp);
        if (200 != $array[0]) {
            throw new LogicException('signature error!', $array[0]);
        }

        return $array[1];
    }

    /**
     * @overide
     *
     * @see \Swallow\Bootstrap\ApiStatisticsInterface#getTransmissionFrom
     *
     * @author    李焯桓 <lizhuohuan@eelly.net>
     *
     * @since 2017年3月12日
     */
    public function getTransmissionFrom()
    {
        return $this->transmissionFrom;
    }

    /**
     * @overide
     *
     * @see \Swallow\Bootstrap\ApiStatisticsInterface#getApp
     *
     * @author    李焯桓 <lizhuohuan@eelly.net>
     *
     * @since 2017年3月12日
     */
    public function getApp()
    {
        return $this->app;
    }

    /**
     * @overide
     *
     * @see \Swallow\Bootstrap\ApiStatisticsInterface#getArgs
     *
     * @author    李焯桓 <lizhuohuan@eelly.net>
     *
     * @since 2017年3月12日
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * @overide
     *
     * @see \Swallow\Bootstrap\ApiStatisticsInterface#getClientName
     *
     * @author    李焯桓 <lizhuohuan@eelly.net>
     *
     * @since 2017年3月12日
     */
    public function getClientName()
    {
        return $this->clientName;
    }

    /**
     * @overide
     *
     * @see \Swallow\Bootstrap\ApiStatisticsInterface#getClientUserType
     *
     * @author    李焯桓 <lizhuohuan@eelly.net>
     *
     * @since 2017年3月12日
     */
    public function getClientUserType()
    {
        return $this->clientUserType;
    }

    /**
     * @overide
     *
     * @see \Swallow\Bootstrap\ApiStatisticsInterface#getClientVersion
     *
     * @author    李焯桓 <lizhuohuan@eelly.net>
     *
     * @since 2017年3月12日
     */
    public function getClientVersion()
    {
        return $this->clientVersion;
    }

    /**
     * @overide
     *
     * @see \Swallow\Bootstrap\ApiStatisticsInterface#getDeviceNumber
     *
     * @author    李焯桓 <lizhuohuan@eelly.net>
     *
     * @since 2017年3月12日
     */
    public function getDeviceNumber()
    {
        return $this->deviceNumber;
    }

    /**
     * @overide
     *
     * @see \Swallow\Bootstrap\ApiStatisticsInterface#getMethod
     *
     * @author    李焯桓 <lizhuohuan@eelly.net>
     *
     * @since 2017年3月12日
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @overide
     *
     * @see \Swallow\Bootstrap\ApiStatisticsInterface#getServiceName
     *
     * @author    李焯桓 <lizhuohuan@eelly.net>
     *
     * @since 2017年3月12日
     */
    public function getServiceName()
    {
        return $this->serviceName;
    }

    /**
     * @overide
     *
     * @see \Swallow\Bootstrap\ApiStatisticsInterface#getUserLoginToken
     *
     * @author    李焯桓 <lizhuohuan@eelly.net>
     *
     * @since 2017年3月12日
     */
    public function getUserLoginToken()
    {
        return $this->userLoginToken;
    }

    /**
     * @overide
     *
     * @see \Swallow\Bootstrap\ApiStatisticsInterface#getUserLoginTokenUserId
     *
     * @author    李焯桓 <lizhuohuan@eelly.net>
     *
     * @since 2017年3月12日
     */
    public function getUserLoginTokenUserId()
    {
        return $this->userLoginTokenUserId;
    }

    /**
     * @overide
     *
     * @see \Swallow\Bootstrap\ApiStatisticsInterface#getHandleInfo
     *
     * @author    李焯桓 <lizhuohuan@eelly.net>
     *
     * @since 2017年3月12日
     */
    public function getHandleInfo()
    {
        return $this->handleInfo;
    }

    /**
     * @overide
     *
     * @see \Swallow\Bootstrap\ApiStatisticsInterface#getHandleRetval
     *
     * @author    李焯桓 <lizhuohuan@eelly.net>
     *
     * @since 2017年3月12日
     */
    public function getHandleRetval()
    {
        return $this->handleRetval;
    }

    /**
     * @overide
     *
     * @see \Swallow\Bootstrap\ApiStatisticsInterface#getHandleStatus
     *
     * @author    李焯桓 <lizhuohuan@eelly.net>
     *
     * @since 2017年3月12日
     */
    public function getHandleStatus()
    {
        return $this->handleStatus;
    }

    /**
     * @overide
     *
     * @see \Swallow\Bootstrap\ApiStatisticsInterface#getRequestData
     *
     * @author    李焯桓 <lizhuohuan@eelly.net>
     *
     * @since 2017年3月12日
     */
    public function getRequestData()
    {
        return $this->requestData;
    }

    /**
     * @overide
     *
     * @see \Swallow\Bootstrap\ApiStatisticsInterface#getRequestDataDecrypt
     *
     * @author    李焯桓 <lizhuohuan@eelly.net>
     *
     * @since 2017年3月12日
     */
    public function getRequestDataDecrypt()
    {
        return $this->requestDataDecrypt;
    }

    /**
     * 获取客户端ip地址
     *
     * @author wangjiang<wangjiang@eelly.net>
     *
     * @since  2017年3月29日
     */
    public function getClientAddress()
    {
        return $this->clientAddress;
    }

    /**
     * 获取sessionId.
     *
     * @author wangjiang<wangjiang@eelly.net>
     *
     * @since  2017年4月14日
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * @param \Throwable $exception
     *
     * @throws \Exception
     */
    private function handleException(\Throwable $exception): void
    {
        static $handler;
        if (null === $handler) {
            $handler = new PlainTextHandler($this->getLogger());
            $handler->loggerOnly(true);
        }
        $handler->setInspector(new Inspector($exception));
        $handler->setException($exception);
        $handler->handle();
    }

    /**
     * @throws \Exception
     *
     * @return Logger
     */
    private function getLogger()
    {
        static $logger;
        if (null === $logger) {
            $logger = new Logger('newmall');
            $config = $this->getDI()->getShared('config');
            $errorFile = $config->path->errorLog.'/app.'.date('Ymd').'.txt';
            $logger->pushHandler(new StreamHandler($errorFile));
            $logger->pushHandler(new DingDingHandler($config->dingdingAccessToken));
        }

        return $logger;
    }

    /**
     * 验证参数.
     *
     * @param array $params
     *
     * @return bool
     */
    private function verifyParam($params)
    {
        if (empty($params['app']) || empty($params['service_name']) || empty($params['method'])) {
            return false;
        }

        return true;
    }

    /**
     * 验证Class.
     *
     * @param string $class 类路径
     *
     * @return mixed
     */
    private function verifyClass($class, $logicName)
    {
        if (empty($class) || empty($logicName)) {
            throw new LogicException('Class does not exist', StatusCode::BAD_REQUEST);
        }
        if (!class_exists($class)) {
            throw new LogicException("class '$class' is not exist", StatusCode::BAD_REQUEST);
        }
        if (!class_exists($logicName)) {
            throw new LogicException("class '$logicName' is not exist", StatusCode::BAD_REQUEST);
        }
    }

    /**
     * 验证 Method.
     *
     * @param string $logicClass
     * @param string $method
     *
     * @return mixed
     */
    private function verifyMethod($logicClass, $method)
    {
        if (empty($method)) {
            throw new LogicException('Method does not exist', StatusCode::BAD_REQUEST);
        }
        if (!method_exists($logicClass, $method)) {
            throw new LogicException("class $logicClass call a non-exist method '$method'", StatusCode::BAD_REQUEST);
        }
    }

    /**
     * 验证权限.
     *
     * @param string $app        系统名
     * @param string $classPath  类名带命名空间
     * @param string $methodName 方法名
     * @param bool   $isOld      是否请求旧的方法
     *
     * @author zengzhihao<zengzhihao@eelly.net>
     *
     * @since  2015年11月30日
     */
    private function verifyPermissions($app, $classPath, $methodName, $isOld)
    {
        // 验证是否可用服务
        $service = explode('\\', $classPath);
        if (!$isOld) {
            unset($service[0], $service[1]);
            if (!empty($this->client)) {
                unset($service[2]);
            }
        }
        $service = implode('\\', $service);
        $client = !empty($this->client) ? $this->client : 'default';
        if (isset(self::$tokenConfig['app_auth'][$app])) {
            $modelService = self::$tokenConfig['app_auth'][$app];
            if (isset($modelService[$service][$client]) && isset($modelService[$service][$client][$methodName])) {
                return $modelService[$service][$client][$methodName] ? true : false;
            }
        }

        throw new LogicException('你没权限访问！', StatusCode::REQUEST_FORBIDDEN);
    }

    /**
     * 校验登陆.
     *
     * @param array $loginData
     *
     * @return mixed
     */
    private function verifyLogin(array $loginData)
    {
        // 参数校验
        if (empty($loginData['user_login_token'])) {
            return false;
        }

        // 获取登录缓存信息
        $cache = $this->getDI()->get('Api\\Lib\\Cache\\DefaultCache', [])->getRedisCache();
        $this->userLoginInfo = $cache->hGetAll('UserLoginTokenInfo:'.$loginData['user_login_token']);
        if (empty($this->userLoginInfo)
                || !isset($this->userLoginInfo['dateline'])
                || $this->userLoginInfo['dateline'] < time()) {
            return false;
        }
        if (isset($this->userLoginInfo['uid'])) {
            $this->userLoginTokenUserId = $this->userLoginInfo['uid'];
        }

        $this->getDI()->getShared('clientInfo')->setLoginUserInfo($this->userLoginInfo);
    }

    /**
     * 解密 加密数据.
     *
     * @param array  $data
     * @param string $transmissionFrom
     * @param string $transmissionVersion
     *
     * @return mixed
     */
    private function decode($data, $transmissionFrom, $transmissionVersion)
    {
        if (empty($data)) {
            throw new LogicException('Request parameter error!', StatusCode::SERVICE_BAD_REQUEST);
        }
        if ($this->isToGetToken) {
            $key = $this->tokenSecret;
        } else {
            if ('Module' == $transmissionFrom) {
                $key = $modulesCrypt = $this->getDI()->getConfig()->appKey;
            } else {
                $key = !empty(self::$tokenConfig['encoding_aes_key']) ? self::$tokenConfig['encoding_aes_key'] : '';
            }
        }
        if (empty($key)) {
            throw new LogicException('access_token invalid ', StatusCode::ACCESS_TOKEN_INVALID);
        }
        $result = json_decode($data, true);
        if (JSON_ERROR_NONE === json_last_error()) {
            $this->isRemoveEncrypt = true;

            return $result;
        } else {
            $data = strrev($data);
            $iv = substr($data, 0, 8);
            $this->desCrypt = new \Swallow\Toolkit\Encrypt\DesCrypt($key, $iv);
            $length = strlen($data) - 14;
            $decodeData = substr($data, 8, $length);
            $data = json_decode($this->desCrypt->decrypt($decodeData), true);
            $this->getLogger()->warning(__METHOD__, ['data' => $data, 'ip' => $this->request->getClientAddress(), 'userAgent' => $this->request->getUserAgent()]);
        }

        return $data;
    }

    /**
     * 日志记录.
     *
     * @param array $data 返回结果
     *
     * @author zengzhihao<zengzhihao@eelly.net>
     * @emendator fenghaikun<fenghaikun@eelly.net>
     *
     * @since  2015年12月14日
     */
    private function logging(array $data): void
    {
        $this->handleStatus = $data['status'] ?? 0;
        $this->handleInfo = $data['info'] ?? '';
        $this->handleRetval = $data['retval'] ?? [];

        if (empty(self::$tokenConfig)) {
            return;
        }
        $tokenInfo = self::$tokenConfig;

        return;
    }

    /**
     * 是否开启测试模式 - 参数校验.
     *
     * @author 李伟权   <liweiquan@eelly.net>
     *
     * @since 2017年3月12日
     *
     * @return bool
     */
    private function isTestVerify()
    {
        return APP_DEBUG && 'Security' != $this->transmissionMode;
    }

    /**
     * 验证v2加密参数.
     *
     * @param array $params
     *
     * @return bool
     *
     * @author 李伟权   <liweiquan@eelly.net>
     *
     * @since 2017年3月12日
     */
    private function verifyV2Param($params)
    {
        if (!$this->verifyParam($params) || empty($params['sign'])) {
            return false;
        }

        return true;
    }

    /**
     * 验证v2加密签名.
     *
     * @param array $params
     *
     * @return bool
     *
     * @author 李伟权   <liweiquan@eelly.net>
     *
     * @since 2017年3月12日
     */
    private function verifyV2Sign($params)
    {
        $sign = $params['sign'];
        unset($params['sign']);
        ksort($params);
        $checkSign = md5(json_encode(array_keys($params)).$this->isToGetToken);
        if ($sign != $checkSign) {
            return false;
        }

        return true;
    }
}
