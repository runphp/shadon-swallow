<?php

namespace Swallow\Toolkit\Net\Client;

/**
 * 客户端
 * 
 * @author    zengzhihao<zengzhihao@eelly.net>
 * @since     2015年12月1日
 * @version   1.0
 */
class Client
{

    /**
     * 请求配置(host:请求地址,encoding_aes_key：数据加解密key,app_id:应用id,app_secret:应用密钥,token:签名密钥)
     * 
     * @var array
     */
    private $config = [];

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
     * 获取客户端单例
     *
     * @param array $config 配置信息
     * @return self
     */
    public static function getInstance(array $config = array())
    {
        if (empty($config['host']) || empty($config['encoding_aes_key']) || empty($config['app_id']) || empty($config['token']) ||
             empty($config['app_secret'])) {
            exit('Client configuration error');
        }
        static $instance = array();
        $key = $config['host'] . $config['app_id'];
        if (! isset($instance[$key])) {
            $instance[$key] = new self($config);
        }
        return $instance[$key];
    }

    /**
     * 服务请求
     * 
     * @param string $app    请求的服务系统
     * @param string $module 请求的服务类
     * @param string $method 请求的服务类的方法
     * @param array $args    请求的服务类的方法的参数
     * @param array $option  可选参数（加密，版本，客户端。。。。。）
     * @author zengzhihao<zengzhihao@eelly.net>
     * @since  2015年12月1日
     */
    public function request($app, $module, $method, $args, $option = [])
    {
        $accessToken = $this->getAccessToken();
        
        $response = $this->getService($accessToken, $app, $module, $method, $args, $option);
        
        //失效重新获取
        if ($response['status'] == 707) {
            $response = $this->getServiceAccessToken();
            if ($response['status'] == 200) {
                $this->setAccessToken($response['retval']);
                $accessToken = $response['retval']['access_token'];
                $response = $this->getService($accessToken, $app, $module, $method, $args, $option);
            }
        }
        
        return $response;
    }

    /**
     * 传递密文请求
     * 
     * @param string $ciphertext 加密串
     * @author zengzhihao<zengzhihao@eelly.net>
     * @since  2015年12月19日
     */
    public function encryptRequest($ciphertext)
    {
        $accessToken = $this->getAccessToken();
        $args = $this->decryption($ciphertext);
        if (! $args) {
            return false;
        }
        $response = $this->getServiceEncrypt($accessToken, $args);
        
        //失效重新获取
        if ($response['status'] == 707) {
            $response = $this->getServiceAccessToken();
            if ($response['status'] == 200) {
                $this->setAccessToken($response['retval']);
                $accessToken = $response['retval']['access_token'];
                $response = $this->getServiceEncrypt($accessToken, $ciphertext);
            }
        }
        
        return ['args' => $args, 'data' => $response];
    }

    /**
     * 请求服务
     * 
     * @param string $accessToken 访问令牌
     * @param string $app    请求的服务系统
     * @param string $module 请求的服务类的命名空间
     * @param string $method 请求的服务类的方法
     * @param array $args    请求的服务类的方法的参数
     * @param array $option  可选参数（加密，版本，客户端。。。。。）
     * @return array
     * @author zengzhihao<zengzhihao@eelly.net>
     * @since  2015年12月1日
     */
    private function getService($accessToken, $app, $module, $method, $args = [], $option = [])
    {
        $encrypt = isset($option['encrypt']) ? $option['encrypt'] : true;
        $version = isset($option['version']) ? $option['version'] : '';
        $client = isset($option['client']) ? $option['client'] : '';
        $userLoginToken = isset($option['user_login_token']) ? $option['user_login_token'] : '';
        $clientName = isset($option['client_name']) ? $option['client_name'] : '';
        $clientVersion = isset($option['client_version']) ? $option['client_version'] : '';
        $clientUserType = isset($option['client_user_type']) ? $option['client_user_type'] : '';
        return \Swallow\Toolkit\Net\Client\Service::getInstance($this->config)->app($app)
            ->module($module)
            ->method($method)
            ->args($args)
            ->accessToken($accessToken)
            ->userLoginToken($userLoginToken)
            ->encrypt($encrypt)
            ->version($version)
            ->client($client)
            ->clientInfo($clientName, $clientVersion, $clientUserType)
            ->exec();
    }

    /**
     * 解密
     *
     * @param string $accessToken 访问令牌
     * @param array  $args  请求参数
     * @return array
     * @author zengzhihao<zengzhihao@eelly.net>
     * @since  2015年12月1日
     */
    private function getServiceEncrypt($accessToken, $args)
    {
        return \Swallow\Toolkit\Net\Client\Service::getInstance($this->config)->accessToken($accessToken)->execDecrypt($args);
    }

    /**
     * 解密
     *
     * @param string $ciphertext  密文串
     * @return array
     * @author zengzhihao<zengzhihao@eelly.net>
     * @since  2015年12月1日
     */
    private function decryption($ciphertext)
    {
        return \Swallow\Toolkit\Net\Client\Service::getInstance($this->config)->deBugDecrypt($ciphertext);
    }

    /**
     * 获取访问令牌
     * 
     * @author zengzhihao<zengzhihao@eelly.net>
     * @since  2015年12月1日
     */
    private function getServiceAccessToken()
    {
        //请求参数
        $args = ['appId' => $this->config['app_id'], 'appSecret' => $this->config['app_secret']];
        //请求的服务类的命名空间
        $module = 'CredentialService';
        //请求的服务类的方法
        $method = 'token';
        //系统
        $app = 'Api';
        
        return \Swallow\Toolkit\Net\Client\Service::getInstance($this->config)->app($app)
            ->module($module)
            ->method($method)
            ->args($args)
            ->token(true)
            ->exec();
    }

    /**
     * 存储access_token到本地
     *
     * @param string $accessToken
     * @author zengzhihao<zengzhihao@eelly.net>
     * @since  2015年12月1日
     */
    private function setAccessToken($data)
    {
        $accessToken = $data['access_token'];
        \Phalcon\Di::getDefault()->getShared('session')->set(md5($this->config['app_id']) . '_access_token', $accessToken);
    }

    /**
     * 获取本地存储的access_token
     *
     * @author zengzhihao<zengzhihao@eelly.net>
     * @since  2015年12月1日
     */
    private function getAccessToken()
    {
        return \Phalcon\Di::getDefault()->getShared('session')->get(md5($this->config['app_id']) . '_access_token');
    }
}
