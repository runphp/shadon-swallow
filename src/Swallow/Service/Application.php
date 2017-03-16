<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Service;

use Api\Logic\ApiResultHistoryLogic;
use Api\Logic\NodeLogic;
use Swallow\Exception\StatusCode;
use Swallow\Exception\LogicException;
use Swallow\Toolkit\Util\Arrays;
use Swallow\Exception\SystemException;

/**
 * service app
 *
 * @author    何辉<hehui@eely.net>
 * @since     2015年9月11日
 * @version   1.0
 */
class Application extends \Phalcon\Mvc\Application
    implements \Swallow\Bootstrap\ApiStatisticsInterface
{

    /**
     * token信息
     * @var array
     */
    private static $tokenConfig = array();

    /**
     * 请求有效时间 30s有效
     * @var int
     */
    private static $expirationTime = 30;

    /**
     * 加密实例
     * @var string
     */
    private $desCrypt = null;

    /**
     * 是否加密传输 默认加密
     * @var boolean
     */
    private $encrypt = false;

    /**
     * 时间戳
     * @var string
     */
    private $timeStamp = '';

    /**
     * 签名密钥
     *
     * @var string
     */
    private $secret = '';

    /**
     * 客户端
     * @var string
     */
    private $client = '';

    /**
     * 是否请求token
     * @var bool
     */
    private $isToGetToken = false;

    /**
     * token加解密密钥
     * @var string
     */
    private $tokenSecret = '%HdoQqwI3sQ3bBnaLReX^hMp';

    /**
     * 请求参数
     * @var array
     */
    private $requestParam = [];

    /**
     * 用户登录信息
     * @var array
     */
    private $userLoginInfo = [];

    /**
     *调试信息
     * @var string
     */
    private $debugInfo = '';

    /**
     *系统时间
     * @var int
     */
    private $sysTimeStamp = '';

    /**
     * 是否【手机端】且【凤凰或以后的版本】
     */
    private $isPhinx = false;
    
    /**
     * 加密版本  （空为默认加密方式   v2:3des加密后再base64加密）
     */
    private $encryptVersion = '';
    
    /**
     * 原始请求数据
     *
     * @var string
     */
    private $requestData;
    
    /**
     * 解密请求数据
     *
     * @var string
     */
    private $requestDataDecrypt;
    
    /**
     * 系统名
     *
     * @var string
     */
    private $app;
    
    /**
     * 服务类名
     *
     * @var string
     */
    private $serviceName;
    
    /**
     * 服务方法名
     *
     * @var string
     */
    private $method;
    
    /**
     * 方法参数
     *
     * @var string
     */
    private $args;
    
    /**
     * 客户端用户名。参考[这里](http://servicemanage.eelly.test/common/account)
     *
     * @var string
     */
    private $clientName;
    
    /**
     * 客户端版本
     *
     * @var string
     */
    private $clientVersion;
    
    
    /**
     * 客户端类型。seller, buyer
     *
     * @var string
     */
    private $clientUserType;
    
    /**
     * 客户端设备ID。针对移动端，可为空
     *
     * @var string
     */
    private $deviceNumber;
    
    /**
     * 接口的access_token
     *
     * @var string
     */
    private $transmissionFrom;
    
    /**
     * 终端用户（个人账号）登录的token
     *
     * @var string
     */
    private $userLoginToken;
    
    /**
     * user_login_token对应的user_id
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
     * 处理结果提示
     *
     * @var string 
     */
    private $handleInfo;
    
    /**
     * 处理结果数据
     *
     * @var array 
     */
    private $handleRetval;
    
    /**
     * 数据模式
     * @var string 
     */
    private $transmissionMode;
    
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
     * @param bool $merge
     * @return \Phalcon\Mvc\Application
     * @author 何辉<hehui@eely.net>
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
                'className' => $ucfirstName . '\Module',
                'path' => "application/$name/src/$ucfirstName/Module.php"];
            $clazzes[$completeModules[$name]['className']] = $completeModules[$name]['path'];
        }
        $loader->registerClasses($clazzes)->register();
        return $this->registerModules($completeModules, $merge);
    }

    public function bootstrap()
    {
        $eventsManager = $this->getEventsManager();
        if (is_object($eventsManager)) {
            $eventsManager->fire("application:beforeBootstrap", $this);
        }
        $data = $this->handle();
        //日志记录
        $this->logging($data, 'response');
        if($this->isTestVerify()){
            return json_encode($data);
        }
        $retval = $signature = '';
        if ($data['status'] == 200) {
            $this->isPhinx || $data['retval'] = Arrays::toString($data['retval']);
            $retval = $data['retval'];
            if ($this->encrypt && $this->desCrypt != null) {
                $retval = json_encode($retval);
                $retval = $this->desCrypt->encrypt($retval);
                !empty($this->encryptVersion) && $this->encryptVersion == 'v2' && $retval = base64_encode($retval);
            }
            //生成签名
            $signature = $this->signature(json_encode($retval));
        }
        $data['retval'] = ['data' => $retval, 'signature' => $signature];
        if (is_object($eventsManager)) {
            $eventsManager->fire("application:afterBootstrap", $this);
        }
        return json_encode($data);
    }

    public function handle($arguments = null)
    {
        $retval = array('status' => StatusCode::OK, 'info' => '', 'retval' => null);
        try {
            $eventsManager = $this->getEventsManager();
            if (is_object($eventsManager)) {
                if ($eventsManager->fire("application:boot", $this) === false) {
                    throw new \ErrorException("application:boot error");
                }
            }
            $defaultDi = $this->getDI();
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
            if (! $this->isToGetToken && !$this->isTestVerify()) {
                //验证access_token
                $res = \Api\Logic\CredentialLogic::getInstance()->verifyAccessToken($this->transmissionFrom);
                self::$tokenConfig = $res['data'];
            }
            if ($this->transmissionMode == 'Security') {
                // 如果启用加密安全传输
                $this->requestData = $this->transmissionMode == 'Security' ? $request->get('data') : $request->getPost('data');
                $this->requestDataDecrypt = $this->decode($this->requestData, $this->transmissionFrom, $transmissionVersion); // 解码
                $verify = $this->verifyParam($this->requestDataDecrypt); // 验证
                if ($verify == false) {
                    $this->debugInfo = '验证verify == false,解码data有问题';
                    throw new LogicException("Request parameter error, or decryption failure!", StatusCode::SERVICE_BAD_REQUEST);
                }
                $this->encrypt = true;
            } else {
                if (APP_DEBUG) {
                    $this->requestData = $this->requestDataDecrypt = 
                            $request->getPost() ? $request->getPost() : $request->get();
                    $this->encrypt = false;
                } else {
                    throw new LogicException("You do not have permission to request!", StatusCode::REQUEST_FORBIDDEN);
                }
            }
            //转换参数
            $isMore = true;
            if ($this->requestDataDecrypt['service_name'] != "Base\\Service\\ClientService" 
                    && $this->requestDataDecrypt['method'] != 'curlMoreReqMallService'){
                $isMore = false;
            }

            $this->app = $this->requestDataDecrypt['app'];
            $this->serviceName = $this->requestDataDecrypt['service_name'];
            $this->method = $this->requestDataDecrypt['method'];
            $this->args = (isset($this->requestDataDecrypt['args']) && $this->requestDataDecrypt['args'] != 'null') 
                    ? $this->requestDataDecrypt['args'] 
                    : null;
            $this->timeStamp = $this->requestDataDecrypt['time'];
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
            $this->getDI()->getShared('clientInfo')->setClearCache($clearCache)->setClientInfo([
                'client_version' => $this->clientVersion,
                'client_name' => $this->clientName,
                'client_user_type' => $this->clientUserType,
                'device_number' => $this->deviceNumber,
            ]);

            $this->isPhinx = in_array(strtolower($this->clientName), ['ios', 'android']) && (($this->clientUserType == 'buyer' && $this->clientVersion >= 430) || ($this->clientUserType == 'seller' && $this->clientVersion >= 220));

            ! empty($version) && $this->method = $this->method . $version;
            if (! is_null($this->args)) {
                $this->args = json_decode($this->args, true);
                is_null($this->args) && $this->args = [];
                if (isset($this->args['clear'])) {
                    if (! empty($this->args['clear'])) {
                        $_ENV['isInternalUser'] = true;
                    }
                    unset($this->args['clear']);
                }
            } else {
                $this->args = [];
            }
            //验证登陆
            $isLogin = false;
            if ($isMore){
                //校验参数
                $newMethods = $oldMethods = [];
                foreach ($this->args['params'] as $key => $newArg){
                    $isOld = false;
                    if (strpos($newArg['module'], '\\') === false) {
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
                    }else{
                        //旧接口
                        $class = $newArg['module'];
                        ! empty($this->client) && $class = str_replace('\\Service\\', '\\Service\\' . $this->client . '\\', $class);
                        $isOld = true;
                        $oldMethods[$key] = $newArg;
                    }
                    $version = isset($newArg['version']) ? intval($newArg['version']) : '';
                    //模块调模块，不验证
                    if ($this->transmissionFrom != 'Module') {
                        //判断是否获取access_token 是则不验证
                        if (! $this->isToGetToken && !$this->isTestVerify()) {
                            //验证权限
                            $isCheckLogin = $this->verifyPermissions($this->app, $class, $newArg['method'].$version, $isOld);
                            $isLogin == false && $isLogin = $isCheckLogin;
                            $this->secret = self::$tokenConfig['token'];
                        } else {
                            $this->secret = $this->tokenSecret;
                        }
                    }
                }
            }else {
                if (strpos($this->serviceName, '\\') === false) {
                    $class = empty($this->client) 
                            ? $this->app . '\Service\\' . $this->serviceName 
                            : $this->app . '\Service\\' . $this->client . '\\' . $this->serviceName;
                } else {
                    $class = $this->serviceName;
                    ! empty($this->client) && $class = str_replace('\\Service\\', '\\Service\\' . $this->client . '\\', $class);
                    $isOld = true;
                }
                if (! $isOld) {
                    $logicName = str_replace('\\Service\\', '\\Logic\\', $class);
                    $logicName = preg_replace('/Service$/', 'Logic', $logicName);
                    $this->verifyClass($class, $logicName); //验证类
                    $this->verifyMethod($logicName, $this->method); //验证方法
                }

                //模块调模块，不验证
                if ($this->transmissionFrom != 'Module') {
                    //判断是否获取access_token 是则不验证
                    if (! $this->isToGetToken && !$this->isTestVerify()) {
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
            //记录本次服务的请求部分
            $this->logging([]);
            // 过滤没有审核通过的接口  返回示例值
            if (APP_DEBUG) {
                // 接口返回示例值的时间限制
                $minDate = '2016-06-14 10:00:00';

                if (!$isMore) {
                    $nodeInfo = NodeLogic::getInstance()->getNodeInfoByServiceMethod($this->serviceName, $this->requestDataDecrypt['method'], $version);

                    // 接口信息不存在
                    if (empty($nodeInfo)) {
                        throw new LogicException($this->serviceName.'服务不存在', 404);
                    }
                    // 未通过审核的接口返回示例值
                    if ($nodeInfo && $nodeInfo['status'] == 0 && $nodeInfo['updateTime'] > $minDate) {
                        $retval['retval'] = json_decode(preg_replace("/\/\*\*([\s\S.])+?\*\*\//", '', $nodeInfo['sample_value']), true);
                        $retval['info'] = $retval['info'].'___示例数据';
                        return $retval;
                    } elseif ($nodeInfo['status'] == 0) {
                        // 接口系统示例值修改时间前的 返回没有权限访问
                        throw new LogicException('你没权限访问！', StatusCode::REQUEST_FORBIDDEN);
                    }
                } else {
                    // 多接口调用 需要检查每一个返回值
                    foreach ($this->args['params'] as $key => $arg) {
                        $nodeInfo = NodeLogic::getInstance()->getNodeInfoByServiceMethod($arg['module'], $arg['method'], $version);

                        if (empty($nodeInfo)) {
                            throw new LogicException($arg['module'] . '\\' . $arg['method'] . '服务不存在', 404);
                        }

                        if ($nodeInfo && $nodeInfo['status'] == 0 && $nodeInfo['updateTime'] > $minDate) {
                            $retval['retval'][$key] = json_decode($nodeInfo['sample_value'], true);
                            $retval['info'] = $retval['info'].'_'.$key.':示例数据';
                            unset($this->args['params'][$key]);
                        } elseif ($nodeInfo['status'] == 0) {
                            // 接口系统示例值修改时间前的 返回没有权限访问
                            throw new LogicException('你没权限访问！', StatusCode::REQUEST_FORBIDDEN);
                        }
                    }

                    // 如果全部是未审核的接口 直接返回
                    if (empty($this->args['params'])) {
                        return $retval;
                    }
                }
            }



            // 验证超时
            if ($this->timeStamp < $this->sysTimeStamp - self::$expirationTime || $this->timeStamp > $this->sysTimeStamp + self::$expirationTime) {
                $this->debugInfo = $this->serviceName . '/' . $this->requestDataDecrypt['method'] . '/this.timeStamp =' . $this->timeStamp . ',time()=' . $this->sysTimeStamp;
                throw new LogicException("Timeout！", StatusCode::REQUEST_TIME_OUT);
            }

            if ($isOld) {
                // 过渡版本 : android和ios客户端，厂+版本2.2.0，店+版本4.3.0之前的版本
                $isTransition   = in_array(strtolower($this->clientName), ['ios', 'android']) && (($this->clientUserType == 'buyer' && $this->clientVersion < 430) || ($this->clientUserType == 'seller' && $this->clientVersion < 220));
                $service        = $isTransition ? 'transitionService' : 'oldService';
                $config         = $this->getDI()->getConfig()->$service->toArray();
                $res            = \Swallow\Toolkit\Net\Service::getInstance($config)->module($this->serviceName)
                    ->method($this->method)
                    ->args($this->args)
                    ->setNewArgs($option)
                    ->setIsPhinx($this->isPhinx)
                    ->exec();
                if ($res['status'] == StatusCode::OK) {
                    $res = $res['retval'];
                } else {
                    throw new LogicException($res['info'], $res['status']);
                }
            } else {
                $class = $this->getDI()->getShared($logicName);
                $methodObj = \Swallow\Core\Reflection::getClass($class)->getMethod($this->method);
                $parameters = $methodObj->getParameters();
                $argsNew = array();
                if (! empty($this->args) && ! empty($parameters)) {
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

            // 记录API调用的结果
            if (APP_DEBUG) {
                if (!$isMore) {
                    ApiResultHistoryLogic::getInstance()->validateApiRet($this->serviceName, $this->method, $version, $res);
                } else {
                    foreach($this->args['params'] as $key => $arg) {
                        ApiResultHistoryLogic::getInstance()->validateApiRet($arg['module'], $arg['method'], $version, $res[$key]);
                    }
                }
            }

            $retval['retval'] = $retval['retval'] ? array_merge($retval['retval'], $res) : $res;
        } catch (LogicException $e) {
            $retval['info'] = $e->getMessage();
            $retval['status'] = $e->getCode();
            $retval['retval'] = $e->getArgs();
        } catch (\Phalcon\Mvc\Model\Exception $e) {
            $retval['info'] = '系统繁忙！';
            $retval['status'] = $e->getCode();
            $retval['retval'] = null;
        } catch (SystemException $e) {
            $retval['info'] = '程序内部错误';
            $retval['status'] = $e->getCode();
            $retval['retval'] = null;
        } catch(\Exception $e) {
            $retval['info'] = '服务系统错误';
            if (APP_DEBUG) {
                $retval['info'] .= ' detail:' .$e->getMessage(). ' in '.$e->getFile() .':'.$e->getLine();
            }
            $retval['retval'] = null;
        }

        return $retval;
    }

    /**
     * 验证参数
     *
     * @param array $params
     * @return boolean
     */
    private function verifyParam($params)
    {
        if (empty($params['app']) || empty($params['service_name']) || empty($params['method']) || empty($params['time'])) {
            return false;
        }
        return true;
    }

    /**
     * 验证Class
     *
     * @param string $class 类路径
     * @return mixed
     */
    private function verifyClass($class, $logicName)
    {
        if (empty($class) || empty($logicName)) {
            throw new LogicException("Class does not exist", StatusCode::BAD_REQUEST);
        }
        if (! class_exists($class)) {
            throw new LogicException("class '$class' is not exist", StatusCode::BAD_REQUEST);
        }
        if (! class_exists($logicName)) {
            throw new LogicException("class '$logicName' is not exist", StatusCode::BAD_REQUEST);
        }
    }

    /**
     * 验证 Method
     *
     * @param string $logicClass
     * @param string $method
     * @return mixed
     */
    private function verifyMethod($logicClass, $method)
    {
        if (empty($method)) {
            throw new LogicException("Method does not exist", StatusCode::BAD_REQUEST);
        }
        if (! method_exists($logicClass, $method)) {
            throw new LogicException("class $logicClass call a non-exist method '$method'", StatusCode::BAD_REQUEST);
        }
    }

    /**
     * 验证权限
     *
     * @param string $app 系统名
     * @param string $classPath 类名带命名空间
     * @param string $methodName 方法名
     * @param bool $isOld 是否请求旧的方法
     * @author zengzhihao<zengzhihao@eelly.net>
     * @since  2015年11月30日
     */
    private function verifyPermissions($app, $classPath, $methodName, $isOld)
    {
        // 验证是否可用服务
        $service = explode('\\', $classPath);
        if (! $isOld) {
            unset($service[0], $service[1]);
            if (! empty($this->client)) {
                unset($service[2]);
            }
        }
        $service = implode('\\', $service);
        $client = ! empty($this->client) ? $this->client : 'default';
        if (isset(self::$tokenConfig['app_auth'][$app])) {
            $modelService = self::$tokenConfig['app_auth'][$app];
            if (isset($modelService[$service][$client]) && isset($modelService[$service][$client][$methodName])) {
                return $modelService[$service][$client][$methodName] ? true : false;
            }
        }

        // 在DEBUG模式下没有审核通过的不限制访问
        if(!APP_DEBUG) {
            throw new LogicException('你没权限访问！', StatusCode::REQUEST_FORBIDDEN);
        }
        return false;
    }

    /**
     * 校验登陆
     *
     * @param array $loginData
     * @return mixed
     */
    private function verifyLogin(array $loginData)
    {
        // 参数校验
        if (empty($loginData['user_login_token'])) {
            return false;
        }

        // 获取登录缓存信息
        $cache = $this->getDI()->get('Api\\Lib\\Cache\\DefaultCache', [])->getLoginCache();
        $this->userLoginInfo = $cache->get($loginData['user_login_token']);
        if (empty($this->userLoginInfo) 
                || ! isset($this->userLoginInfo['dateline']) 
                || $this->userLoginInfo['dateline'] < time()) {
           return false;
        }
        if (isset($this->userLoginInfo['uid'])) {
            $this->userLoginTokenUserId = $this->userLoginInfo['uid'];
        }

        $this->getDI()->getShared('clientInfo')->setLoginUserInfo($this->userLoginInfo);
    }

    /**
     * 生成签名
     *
     * @param string $msg 消息
     * @author zengzhihao<zengzhihao@eelly.net>
     * @since  2015年12月1日
     */
    public function signature($msg)
    {
        //生成安全签名
        $sha1 = new \Swallow\Toolkit\Encrypt\Sha1();
        $array = $sha1->getSHA1($msg, $this->secret, $this->timeStamp);
        if ($array[0] != 200) {
            throw new LogicException("signature error!", $array[0]);
        }
        return $array[1];
    }

    /**
     * 解密 加密数据
     *
     * @param array $data
     * @param string $transmissionFrom
     * @param string $transmissionVersion
     * @return mixed
     */
    private function decode($data, $transmissionFrom, $transmissionVersion)
    {
        if (empty($data)) {
            throw new LogicException("Request parameter error!", StatusCode::SERVICE_BAD_REQUEST);
        }
        if ($this->isToGetToken) {
            $key = $this->tokenSecret;
        } else {
            if ($transmissionFrom == 'Module') {
                $key = $modulesCrypt = $this->getDI()->getConfig()->appKey;
            } else {
                $key = ! empty(self::$tokenConfig['encoding_aes_key']) ? self::$tokenConfig['encoding_aes_key'] : '';
            }
        }
        if (empty($key)) {
            throw new LogicException("access_token invalid ", StatusCode::ACCESS_TOKEN_INVALID);
        }
        $data = strrev($data);
        $iv = substr($data, 0, 8);
        $this->desCrypt = new \Swallow\Toolkit\Encrypt\DesCrypt($key, $iv);
        $length = strlen($data) - 14;
        $decodeData = substr($data, 8, $length);
        !empty($transmissionVersion) && $transmissionVersion == 'v2' && $decodeData = base64_decode($decodeData);
        $data = json_decode($this->desCrypt->decrypt($decodeData), true);
        return $data;
    }

    /**
     * 日志记录
     *
     * @param array $data 返回结果
     * @author zengzhihao<zengzhihao@eelly.net>
     * @emendator fenghaikun<fenghaikun@eelly.net>
     * @since  2015年12月14日
     */
    private function logging(array $data, $stage = 'request')
    {
        $this->handleStatus = isset($data['status']) ? $data['status'] : 0;
        $this->handleInfo = isset($data['info']) ? $data['info'] : '';
        $this->handleRetval = isset($data['retval']) ? $data['retval'] :[];
        
        if (empty(self::$tokenConfig)) {
            return;
        }
        $tokenInfo = self::$tokenConfig;
        $userLoginInfo = !empty($this->userLoginInfo) ? $this->userLoginInfo : [];
        //生成唯一字符串用于标识一个完整的请求和响应
        $uniqueStr = md5(implode(',', array_merge(self::$tokenConfig, $userLoginInfo, $this->requestParam)));
        $nowTime = \Swallow\Toolkit\Util\Time::getSystemTime(1);
        $logData = [
            'user_id' => isset($userLoginInfo['uid']) ? $userLoginInfo['uid'] : '',
            'client_name' => $tokenInfo['app_name'],
            'client_version' => $this->clientVersion,
            'client_user_type' => $this->clientUserType,
            'unique_str' => $uniqueStr,
            'status' => isset($data['status']) ? $data['status'] : 0,
            'access_token_info' => $tokenInfo,
            'user_login_info' => $userLoginInfo,
            'request_param' => $this->requestParam,
            'return_data' => $data,
            'exception_debug_info' => $this->debugInfo,
            'strat_time' => 'request' == $stage ? $nowTime : 0,
            'end_time' => 'response' == $stage ? $nowTime : 0,
            'create_time' => time(),
        ];
        //记录日志
        \Api\Service\LogService::getInstance()->serviceRequestLog($logData);
        return ;
        unset($tokenInfo['app_auth']);
        $logger = $this->getDI()->getLogger();

        //记录每次请求信息，如果是非200的正常返回，多记录返回给用户看的数据
        $serviceLogPath = $this->getDI()->getConfig()->path->serviceLog;
        $logDir = $serviceLogPath.'/' . $tokenInfo['app_name'] . '/' . date('Ym') . '/' . date('Ymd');
        $logName = $data['status'] == 200 ? 'app_access_200'. '_' . date('Ymd_H') : 'app_access_' . $data['status'] . '_' . date('Ymd_H');
        $logStr['access_token_info'] = $tokenInfo;
        $logStr['user_login_info'] = $this->userLoginInfo;
        $logStr['request_param'] = $this->requestDataDecrypt;
        //APP_DEBUG 模式开启数据记录
        $logStr['return_data'] = ($data['status'] == 200 && !APP_DEBUG ) ? '[正常数据,隐藏]' : $data;
        //不是正常返回200的，额外记录一些调试信息
        ($data['status'] != 200 || !empty($this->debugInfo)) && $logStr['exception_debug_info'] = $this->debugInfo;
        $logStr = PHP_EOL .var_export($logStr, true);

        //按uid划分日志
        if (\Phalcon\Di::getDefault()->getConfig()->isUserLog && isset($this->userLoginInfo['uid'])) {
            $logDir = $serviceLogPath.'/' . $tokenInfo['app_name'] . '/uid_' . $this->userLoginInfo['uid'] . '/' . date('Ym') . '/' . date('Ymd');
            $logger->setDir($logDir)->setName($logName)->record($logStr)->save();
        } else {
            $logger->setDir($logDir)->setName($logName)->record($logStr)->save();
        }
    }

    /**
     * @overide
     * @see \Swallow\Bootstrap\ApiStatisticsInterface#getTransmissionFrom
     * 
     * @author    李焯桓 <lizhuohuan@eelly.net>
     * @since 2017年3月12日
     */
    public function getTransmissionFrom()
    {
        return $this->transmissionFrom;
    }

    /**
     * @overide
     * @see \Swallow\Bootstrap\ApiStatisticsInterface#getApp
     * 
     * @author    李焯桓 <lizhuohuan@eelly.net>
     * @since 2017年3月12日
     */
    public function getApp()
    {
        return $this->app;
    }

    /**
     * @overide
     * @see \Swallow\Bootstrap\ApiStatisticsInterface#getArgs
     * 
     * @author    李焯桓 <lizhuohuan@eelly.net>
     * @since 2017年3月12日
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * @overide
     * @see \Swallow\Bootstrap\ApiStatisticsInterface#getClientName
     * 
     * @author    李焯桓 <lizhuohuan@eelly.net>
     * @since 2017年3月12日
     */
    public function getClientName()
    {
        return $this->clientName;
    }

    /**
     * @overide
     * @see \Swallow\Bootstrap\ApiStatisticsInterface#getClientUserType
     * 
     * @author    李焯桓 <lizhuohuan@eelly.net>
     * @since 2017年3月12日
     */
    public function getClientUserType()
    {
        return $this->clientUserType;
    }

    /**
     * @overide
     * @see \Swallow\Bootstrap\ApiStatisticsInterface#getClientVersion
     * 
     * @author    李焯桓 <lizhuohuan@eelly.net>
     * @since 2017年3月12日
     */
    public function getClientVersion()
    {
        return $this->clientVersion;
    }

    /**
     * @overide
     * @see \Swallow\Bootstrap\ApiStatisticsInterface#getDeviceNumber
     * 
     * @author    李焯桓 <lizhuohuan@eelly.net>
     * @since 2017年3月12日
     */
    public function getDeviceNumber()
    {
        return $this->deviceNumber;
    }

    /**
     * @overide
     * @see \Swallow\Bootstrap\ApiStatisticsInterface#getMethod
     * 
     * @author    李焯桓 <lizhuohuan@eelly.net>
     * @since 2017年3月12日
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @overide
     * @see \Swallow\Bootstrap\ApiStatisticsInterface#getServiceName
     * 
     * @author    李焯桓 <lizhuohuan@eelly.net>
     * @since 2017年3月12日
     */
    public function getServiceName()
    {
        return $this->serviceName;
    }

    /**
     * @overide
     * @see \Swallow\Bootstrap\ApiStatisticsInterface#getUserLoginToken
     * 
     * @author    李焯桓 <lizhuohuan@eelly.net>
     * @since 2017年3月12日
     */
    public function getUserLoginToken()
    {
        return $this->userLoginToken;
    }

    /**
     * @overide
     * @see \Swallow\Bootstrap\ApiStatisticsInterface#getUserLoginTokenUserId
     * 
     * @author    李焯桓 <lizhuohuan@eelly.net>
     * @since 2017年3月12日
     */
    public function getUserLoginTokenUserId()
    {
        return $this->userLoginTokenUserId;
    }

    /**
     * @overide
     * @see \Swallow\Bootstrap\ApiStatisticsInterface#getHandleInfo
     * 
     * @author    李焯桓 <lizhuohuan@eelly.net>
     * @since 2017年3月12日
     */
    public function getHandleInfo()
    {
        return $this->handleInfo;
    }

    /**
     * @overide
     * @see \Swallow\Bootstrap\ApiStatisticsInterface#getHandleRetval
     * 
     * @author    李焯桓 <lizhuohuan@eelly.net>
     * @since 2017年3月12日
     */
    public function getHandleRetval()
    {
        return $this->handleRetval;
    }

    /**
     * @overide
     * @see \Swallow\Bootstrap\ApiStatisticsInterface#getHandleStatus
     * 
     * @author    李焯桓 <lizhuohuan@eelly.net>
     * @since 2017年3月12日
     */
    public function getHandleStatus()
    {
        return $this->handleStatus;
    }

    /**
     * @overide
     * @see \Swallow\Bootstrap\ApiStatisticsInterface#getRequestData
     * 
     * @author    李焯桓 <lizhuohuan@eelly.net>
     * @since 2017年3月12日
     */
    public function getRequestData()
    {
        return $this->requestData;
    }

    /**
     * @overide
     * @see \Swallow\Bootstrap\ApiStatisticsInterface#getRequestDataDecrypt
     * 
     * @author    李焯桓 <lizhuohuan@eelly.net>
     * @since 2017年3月12日
     */
    public function getRequestDataDecrypt()
    {
        return $this->requestDataDecrypt;
    }
    
    /**
     * 是否开启测试模式 - 参数校验
     * 
     * @author 李伟权   <liweiquan@eelly.net>
     * @since 2017年3月12日
     * @return boolean
     */
    private function isTestVerify()
    {
        return APP_DEBUG && $this->transmissionMode != 'Security';
    }

}
