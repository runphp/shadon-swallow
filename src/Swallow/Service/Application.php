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
    private $isToken = false;

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
        $data = $this->handle();
        //日志记录
        $this->logging($data);
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
            $transmissionMode = $request->getHeader('Transmission-Mode');
            $transmissionModeTcp = $request->get('Transmission-Mode');
            $transmissionMode = $transmissionMode ? $transmissionMode : $transmissionModeTcp;
            $transmissionFrom = $request->getHeader('Transmission-From');
            $transmissionFrom = $transmissionFrom ? $transmissionFrom : $request->get('Transmission-From');
            $transmissionToken = $request->getHeader('Transmission-Token');
            $transmissionVersion = $request->getHeader('Transmission-Version');
            $this->encryptVersion = empty($transmissionVersion) ? '' : $transmissionVersion;
            $this->isToken = $transmissionToken ? $transmissionToken : $request->get('Transmission-Token');
            $isOld = false;
            $option = [];
            if (! $this->isToken) {
                //验证access_token
                $res = \Api\Logic\CredentialLogic::getInstance()->verifyAccessToken($transmissionFrom);
                self::$tokenConfig = $res['data'];
            }
            if ($transmissionMode == 'Security') {
                // 如果启用加密安全传输
                $data = $transmissionModeTcp == 'Security' ? $request->get('data') : $request->getPost('data');
                $data = $this->decode($data, $transmissionFrom, $transmissionVersion); // 解码
                $verify = $this->verifyParam($data); // 验证
                if ($verify == false) {
                    $this->debugInfo = '验证verify == false,解码data有问题';
                    throw new LogicException("Request parameter error, or decryption failure!", StatusCode::SERVICE_BAD_REQUEST);
                }
                $this->encrypt = true;
            } else {
                if (APP_DEBUG) {
                    $data = $request->getPost() ? $request->getPost() : $request->get();
                    $this->encrypt = false;
                } else {
                    throw new LogicException("You do not have permission to request!", StatusCode::REQUEST_FORBIDDEN);
                }
            }
            //转换参数
            $isMore = true;
            if ($data['service_name'] != "Base\\Service\\ClientService" && $data['method'] != 'curlMoreReqMallService'){
                $isMore = false;
            }

            $this->requestParam = $data;
            $app = $data['app'];
            $serviceName = $data['service_name'];
            $method = $data['method'];
            $parameter = (isset($data['args']) && $data['args'] != 'null') ? $data['args'] : null;
            $this->timeStamp = $data['time'];
            $version = isset($data['version']) ? $data['version'] : '';
            $this->client = isset($data['client']) ? $data['client'] : '';
            // 登陆信息
            $userLoginToken = $option['user_login_token'] = isset($data['user_login_token']) ? $data['user_login_token'] : '';
            // 客户端信息
            $clearCache = $option['clear_cache'] = isset($data['clear_cache']) ? $data['clear_cache'] : '';
            $clientVersion = $option['client_version'] = isset($data['client_version']) ? $data['client_version'] : '';
            $clientName = $option['client_name'] = isset($data['client_name']) ? $data['client_name'] : '';
            $clientUserType = $option['client_user_type'] = isset($data['client_user_type']) ? $data['client_user_type'] : '';
            $clientDevice = $option['device_number'] = isset($data['device_number']) ? $data['device_number'] : '';
            $this->getDI()->getShared('clientInfo')->setClearCache($clearCache)->setClientInfo([
                'client_version' => $clientVersion,
                'client_name' => $clientName,
                'client_user_type' => $clientUserType,
                'device_number' => $clientDevice,
            ]);

            $this->isPhinx = in_array(strtolower($clientName), ['ios', 'android']) && (($clientUserType == 'buyer' && $clientVersion >= 430) || ($clientUserType == 'seller' && $clientVersion >= 220));

            ! empty($version) && $method = $method . $version;
            $args = [];
            if (! is_null($parameter)) {
                $args = json_decode($parameter, true);
                is_null($args) && $args = [];
                if (isset($args['clear'])) {
                    if (! empty($args['clear'])) {
                        $_ENV['isInternalUser'] = true;
                    }
                    unset($args['clear']);
                }
            }
            //验证登陆
            $isLogin = false;
            if ($isMore){
                //校验参数
                $newMethods = $oldMethods = [];
                foreach ($args['params'] as $key => $newArg){
                    $isOld = false;
                    if (strpos($newArg['module'], '\\') === false) {
                        //新接口
                        //$class = empty($this->client) ? $app . '\Service\\' . $newArg['module'] : $app . '\Service\\' . $this->client . '\\' .$newArg['module'];
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
                    if ($transmissionFrom != 'Module') {
                        //判断是否获取access_token 是则不验证
                        if (! $this->isToken) {
                            //验证权限
                            $isCheckLogin = $this->verifyPermissions($app, $class, $newArg['method'].$version, $isOld);
                            $isLogin == false && $isLogin = $isCheckLogin;
                            $this->secret = self::$tokenConfig['token'];
                        } else {
                            $this->secret = $this->tokenSecret;
                        }
                    }
                }
            }else {
                if (strpos($serviceName, '\\') === false) {
                    $class = empty($this->client) ? $app . '\Service\\' . $serviceName : $app . '\Service\\' . $this->client . '\\' .
                         $serviceName;
                } else {
                    $class = $serviceName;
                    ! empty($this->client) && $class = str_replace('\\Service\\', '\\Service\\' . $this->client . '\\', $class);
                    $isOld = true;
                }
                if (! $isOld) {
                    $logicName = str_replace('\\Service\\', '\\Logic\\', $class);
                    $logicName = preg_replace('/Service$/', 'Logic', $logicName);
                    $this->verifyClass($class, $logicName); //验证类
                    $this->verifyMethod($logicName, $method); //验证方法
                }

                //模块调模块，不验证
                if ($transmissionFrom != 'Module') {
                    //判断是否获取access_token 是则不验证
                    if (! $this->isToken) {
                        //验证权限
                        $isLogin = $this->verifyPermissions($app, $class, $method, $isOld);
                        $this->secret = self::$tokenConfig['token'];
                    } else {
                        $this->secret = $this->tokenSecret;
                    }
                }
            }

            // 接口系统不再校验登录，只用作日志记录
            !empty($userLoginToken) && $this->verifyLogin(['user_login_token' => $userLoginToken]);

            // 过滤没有审核通过的接口  返回示例值
            if (APP_DEBUG) {
                // 接口返回示例值的时间限制
                $minDate = '2016-06-14 10:00:00';

                if (!$isMore) {
                    $nodeInfo = NodeLogic::getInstance()->getNodeInfoByServiceMethod($serviceName, $data['method'], $version);

                    // 接口信息不存在
                    if (empty($nodeInfo)) {
                        throw new LogicException($serviceName.'服务不存在', 404);
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
                    foreach ($args['params'] as $key => $arg) {
                        $nodeInfo = NodeLogic::getInstance()->getNodeInfoByServiceMethod($arg['module'], $arg['method'], $version);

                        if (empty($nodeInfo)) {
                            throw new LogicException($arg['module'] . '\\' . $arg['method'] . '服务不存在', 404);
                        }

                        if ($nodeInfo && $nodeInfo['status'] == 0 && $nodeInfo['updateTime'] > $minDate) {
                            $retval['retval'][$key] = json_decode($nodeInfo['sample_value'], true);
                            $retval['info'] = $retval['info'].'_'.$key.':示例数据';
                            unset($args['params'][$key]);
                        } elseif ($nodeInfo['status'] == 0) {
                            // 接口系统示例值修改时间前的 返回没有权限访问
                            throw new LogicException('你没权限访问！', StatusCode::REQUEST_FORBIDDEN);
                        }
                    }

                    // 如果全部是未审核的接口 直接返回
                    if (empty($args['params'])) {
                        return $retval;
                    }
                }
            }



            // 验证超时
            if ($this->timeStamp < $this->sysTimeStamp - self::$expirationTime || $this->timeStamp > $this->sysTimeStamp + self::$expirationTime) {
                $this->debugInfo = $serviceName . '/' . $data['method'] . '/this.timeStamp =' . $this->timeStamp . ',time()=' . $this->sysTimeStamp;
                throw new LogicException("Timeout！", StatusCode::REQUEST_TIME_OUT);
            }

            if ($isOld) {
                // 过渡版本 : android和ios客户端，厂+版本2.2.0，店+版本4.3.0之前的版本
                $isTransition   = in_array(strtolower($clientName), ['ios', 'android']) && (($clientUserType == 'buyer' && $clientVersion < 430) || ($clientUserType == 'seller' && $clientVersion < 220));
                $service        = $isTransition ? 'transitionService' : 'oldService';
                $config         = $this->getDI()->getConfig()->$service->toArray();
                $res            = \Swallow\Toolkit\Net\Service::getInstance($config)->module($serviceName)
                    ->method($method)
                    ->args($args)
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
                $methodObj = \Swallow\Core\Reflection::getClass($class)->getMethod($method);
                $parameters = $methodObj->getParameters();
                $argsNew = array();
                if (! empty($args) && ! empty($parameters)) {
                    foreach ($parameters as $val) {
                        if (isset($args[$val->name])) {
                            $argsNew[] = $args[$val->name];
                        } elseif ($val->isDefaultValueAvailable()) {
                            $argsNew[] = $val->getDefaultValue();
                        }
                    }
                }
                $res = call_user_func_array([$class, $method], $argsNew);
            }

            // 记录API调用的结果
            if (APP_DEBUG) {
                if (!$isMore) {
                    ApiResultHistoryLogic::getInstance()->validateApiRet($serviceName, $method, $version, $res);
                } else {
                    foreach($args['params'] as $key => $arg) {
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
        $cache = $this->getDI()->getShared('defaultCache')->getLoginCache();
        $this->userLoginInfo = $userLoginInfo = $cache->get($loginData['user_login_token']);
        if (empty($userLoginInfo) || ! isset($userLoginInfo['dateline']) || $userLoginInfo['dateline'] < time()) {
           return false;
        }

        $this->getDI()->getShared('clientInfo')->setLoginUserInfo($userLoginInfo);
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
        if ($this->isToken) {
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
    private function logging(array $data)
    {
        if (empty(self::$tokenConfig)) {
            return;
        }
        $tokenInfo = self::$tokenConfig;
        unset($tokenInfo['app_auth']);
        $logger = $this->getDI()->getLogger();

        //记录每次请求信息，如果是非200的正常返回，多记录返回给用户看的数据
        $serviceLogPath = $this->getDI()->getConfig()->path->serviceLog;
        $logDir = $serviceLogPath.'/' . $tokenInfo['app_name'] . '/' . date('Ym') . '/' . date('Ymd');
        $logName = $data['status'] == 200 ? 'app_access_200'. '_' . date('Ymd_H') : 'app_access_' . $data['status'] . '_' . date('Ymd_H');
        $logStr['access_token_info'] = $tokenInfo;
        $logStr['user_login_info'] = $this->userLoginInfo;
        $logStr['request_param'] = $this->requestParam;
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
}
