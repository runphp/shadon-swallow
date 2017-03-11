<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Socket;

use Api\Logic\ApiResultHistoryLogic;
use Api\Logic\NodeLogic;
use Swallow\Exception\StatusCode;
use Swallow\Exception\LogicException;
use Swallow\Toolkit\Util\Arrays;
use Swallow\Exception\SystemException;

/**
 * service app
 *
 * @author    lizhuohuan<lizhuohuan@eelly.net>
 * @since     2017年2月6日
 * @version   1.0
 */
class Application extends \Phalcon\Mvc\Application
{
    const LOG_TRACE = 1;
    
    const LOG_DEBUG = 2;
    
    const LOG_INFO = 3;
    
    const LOG_WARNING = 4;
    
    const LOG_ERROR = 5;

    /**
     * token信息
     * @var array
     */
    private $tokenConfig = array();

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
     * 加密版本  （空为默认加密方式   v2:3des加密后再base64加密）
     */
    private $encryptVersion = '';
    
    /**
     *
     * @var 请求的数据类型 
     */
    private $requestType;
    
    /**
     * 客户端的请求标识
     *
     * @var type 
     */
    private $requestIdentify;

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
    
    public function setTokenConfig(array $tokenConfig) 
    {
        $this->tokenConfig = $tokenConfig;
    }
    
    public function getTokenConfig() 
    {
        return $this->tokenConfig;
    }

    public function bootstrap()
    {
        $data = $this->handle();
        //日志记录
        $this->logging($data);
        $retval = $signature = '';
        if ($data['status'] == 200) {
            $retval = json_encode($data['retval']);
            $retval = $this->desCrypt->encrypt($retval);
            //生成签名
            $signature = $this->signature(json_encode($retval));
        }
        $data['retval'] = ['data' => $retval, 'signature' => $signature];
        return json_encode($data);
    }

    public function handle($arguments = null)
    {
        $retval = ['status' => StatusCode::OK, 'info' => '', 'retval' => null];
        try {
            $eventsManager = $this->getEventsManager();
            if (is_object($eventsManager)) {
                if ($eventsManager->fire("application:boot", $this) === false) {
                    throw new \ErrorException("application:boot error");
                }
            }
            $defaultDi = $this->getDI();
            $request = $defaultDi->getRequest();
            $isOld = false;
            $option = [];
            
            $data = $request->getPost('data');
            $data = $this->decode($data); // 解码
            if (empty($data)) {
                $this->debugInfo = '解码data有问题';
                throw new LogicException("Decryption failure!", StatusCode::SERVICE_BAD_REQUEST);
            }
            $verify = $this->verifyParam($data); // 验证
            if ($verify['status'] != StatusCode::OK) {
                $this->debugInfo = '验证参数有问题, ' . $verify['msg'];
                throw new LogicException("Request parameter error, " . $verify['msg'], StatusCode::SERVICE_BAD_REQUEST);
            }
            $this->encrypt = true;
            //用于判断是否多接口同时调用
            $isApiHub = $data['service_name'] == "Base\\Service\\ClientService" && $data['method'] == 'curlMoreReqMallService';

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
            // 请求标识相关
            $this->requestType = $data['type'];
            $this->requestIdentify = $data['identify'];
            $this->getDI()->getShared('clientInfo')->setClearCache($clearCache)->setClientInfo([
                'client_version' => $clientVersion,
                'client_name' => $clientName,
                'client_user_type' => $clientUserType,
                'device_number' => $clientDevice,
            ]);

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
            if ($isApiHub) {
                //校验参数
                $newMethods = $oldMethods = [];
                foreach ($args['params'] as $key => $newArg){
                    $isOld = false;
                    if (strpos($newArg['module'], '\\') === false) {
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
                    //验证权限
                    $isCheckLogin = $this->verifyPermissions($app, $class, $newArg['method'].$version, $isOld);
                    $isLogin == false && $isLogin = $isCheckLogin;
                    $this->secret = $this->tokenConfig['token'];
                }
            } else {
                if (strpos($serviceName, '\\') === false) {
                    $class = empty($this->client) 
                            ? $app . '\Service\\' . $serviceName 
                            : $app . '\Service\\' . $this->client . '\\' . $serviceName;
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
                
                //验证权限
                $isLogin = $this->verifyPermissions($app, $class, $method, $isOld);
                $this->secret = $this->tokenConfig['token'];
            }

            // 接口系统不再校验登录，只用作日志记录
            !empty($userLoginToken) && $this->verifyLogin(['user_login_token' => $userLoginToken]);

            // 过滤没有审核通过的接口  返回示例值
            if (APP_DEBUG) {
                // 接口返回示例值的时间限制 ,之前的接口没有示例值
                $minDate = '2016-06-14 10:00:00';

                if (!$isApiHub) {
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
            if (!APP_DEBUG && ($this->timeStamp < $this->sysTimeStamp - self::$expirationTime || $this->timeStamp > $this->sysTimeStamp + self::$expirationTime)) {
                $this->debugInfo = $serviceName . '/' . $data['method'] . '/this.timeStamp =' . $this->timeStamp . ',time()=' . $this->sysTimeStamp;
                throw new LogicException("Timeout！", StatusCode::REQUEST_TIME_OUT);
            }

            if ($isOld) {
                $config         = $this->getDI()->getConfig()->oldService->toArray();
                $res            = \Swallow\Toolkit\Net\Service::getInstance($config)->module($serviceName)
                    ->method($method)
                    ->args($args)
                    ->setNewArgs($option)
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
                if (!$isApiHub) {
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
            $retval['retval'] = (Object)[];
        } catch (SystemException $e) {
            $retval['info'] = '程序内部错误';
            $retval['status'] = $e->getCode();
            $retval['retval'] = (Object)[];
        } catch(\Exception $e) {
            $retval['info'] = '服务系统错误';
            if (APP_DEBUG) {
                $retval['info'] .= ' detail:' .$e->getMessage(). ' in '.$e->getFile() .':'.$e->getLine();
            }
            $retval['retval'] = (Object)[];
        }
        $retval['retval']['type'] = $this->requestType;
        $retval['retval']['identify'] = $this->requestIdentify;

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
        $rs = [
            'status' => StatusCode::OK, 
            'msg' => '',
            'retval' => [],
        ];
        if (empty($params['app'])) {
            $rs['status'] = StatusCode::BAD_REQUEST;
            $rs['msg'] .= " params: app empty";
        }
        if (empty($params['service_name'])) {
            $rs['status'] = StatusCode::BAD_REQUEST;
            $rs['msg'] .= " params: service_name empty";
        }
        if (empty($params['method'])) {
            $rs['status'] = StatusCode::BAD_REQUEST;
            $rs['msg'] .= " params: method empty";
        }
        if (empty($params['time'])) {
            $rs['status'] = StatusCode::BAD_REQUEST;
            $rs['msg'] .= " params: time empty";
        }
        if (empty($params['type'])) {
            $rs['status'] = StatusCode::BAD_REQUEST;
            $rs['msg'] .= " params: type empty";
        }
        if (empty($params['identify'])) {
            $rs['status'] = StatusCode::BAD_REQUEST;
            $rs['msg'] .= " params: identify empty";
        }
        
        return $rs;
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
        if (isset($this->tokenConfig['app_auth'][$app])) {
            $modelService = $this->tokenConfig['app_auth'][$app];
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
     * 日志记录
     *
     * @param $data 要记录的日志数据
     * @parm int 日志级别
     * @author lizhuohuan<lizhuohuan@eelly.net>
     * @since  2017年3月3日
     */
    public function log($data, $level = 'trace')
    {
        $filePre = 'trace';
        switch ($level) {
            case self::LOG_TRACE:
                $filePre = 'trace';
                break;
            case self::LOG_DEBUG: 
                $filePre = 'debug';
                break;
            case self::LOG_INFO: 
                $filePre = 'info';
                break;
            case self::LOG_WARNING:
                $filePre = 'warning';
                break;
            case self::LOG_ERROR:
                $filePre = 'error';
                break;
        }
        
        $logger = $this->getDI()->getLogger();
        $logPath = $this->getDI()->getConfig()->path->serviceLog;
        $logDir = $logPath.'/socket/' . date('Ym') . '/' . date('Ymd');
        $logFileName = $filePre . '_' . date('Ymd_H');
        $logTime = date('Y.m.d H:i:s');
        $logInfo = [
            "==================  " . $filePre . "  start  ==================", 
            var_export($data, true), 
            "==================  " . $filePre . "  end  ==================", 
        ];
        $logStr = implode(PHP_EOL, $logInfo) . PHP_EOL . PHP_EOL;
        $logger->setDir($logDir)->setName($logFileName)->record($logStr)->save();
    }

    /**
     * 解密 加密数据
     *
     * @param array $data
     * @return mixed
     */
    private function decode($data)
    {
        if (empty($data)) {
            throw new LogicException("Request parameter error!", StatusCode::SERVICE_BAD_REQUEST);
        }
        $key = ! empty($this->tokenConfig['encoding_aes_key']) ? $this->tokenConfig['encoding_aes_key'] : '';
        if (empty($key)) {
            throw new LogicException("access_token invalid ", StatusCode::ACCESS_TOKEN_INVALID);
        }
        $data = strrev($data);
        $iv = substr($data, 0, 8);
        $this->desCrypt = new \Swallow\Toolkit\Encrypt\DesCrypt($key, $iv);
        $length = strlen($data) - 14;
        $decodeData = substr($data, 8, $length);
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
