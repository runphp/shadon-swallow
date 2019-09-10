<?php

/**
 *  极光推送
 * @author  zhangzeqiang <zhangzeqiang@eelly.com>
 */
namespace Swallow\Toolkit\Net\JPush;

use Swallow\Core\Conf;
use Swallow\Exception\LogicException;
use Swallow\Exception\StatusCodeInfo;

require_once("Core/PushPayload.php");
require_once("Core/ReportPayload.php");
require_once("Core/DevicePayload.php");
require_once("Core/SchedulePayload.php");
require_once("Core/JPushException.php");

class JPush {
    const DISABLE_SOUND = "_disable_Sound";
    const DISABLE_BADGE = 0x10000;
    const USER_AGENT = 'JPush-API-PHP-Client';
    const CONNECT_TIMEOUT = 5;
    const READ_TIMEOUT = 30;
    const DEFAULT_MAX_RETRY_TIMES = 3;
    const DEFAULT_LOG_FILE = "j_push_log";//"./jpush";
    const HTTP_GET = 'GET';
    const HTTP_POST = 'POST';
    const HTTP_DELETE = 'DELETE';
    const HTTP_PUT = 'PUT';
    
    private $appKey;
    private $masterSecret;
    private $retryTimes;
    private $logFile;
    
    public static function getInstance($appKey, $masterSecret, $type = 'buyer', $logFile=self::DEFAULT_LOG_FILE, $retryTimes=self::DEFAULT_MAX_RETRY_TIMES)
    {
        static $instance = [];
        $instanceKey = 'jpush' . $type;
        if (! isset($instance[$instanceKey])) {
            $instance[$instanceKey] = new self($appKey, $masterSecret, $logFile, $retryTimes);
        }
        return $instance[$instanceKey];
    }
    
    /**
     * 初始化
     * 
     * @param unknown $appKey
     * @param unknown $masterSecret
     * @param unknown $logFile
     * @param unknown $retryTimes
     * @throws InvalidArgumentException
     * @author 张泽强<zhangzeqiang@eelly.net>
     * @since  2016年5月30日
     */
    public function __construct($appKey, $masterSecret, $logFile=self::DEFAULT_LOG_FILE, $retryTimes=self::DEFAULT_MAX_RETRY_TIMES) {
        if (is_null($appKey) || is_null($masterSecret)) {
            throw new LogicException("appKey and masterSecret must be set.", StatusCodeInfo::JPUSH_PARAM_ERROR);
        }
    
        if (!is_string($appKey) || !is_string($masterSecret)) { 
            throw new LogicException("Invalid appKey or masterSecret.", StatusCodeInfo::JPUSH_PARAM_ERROR);
        }
        $this->appKey = $appKey;
        $this->masterSecret = $masterSecret;
        if (!is_null($retryTimes)) {
            $this->retryTimes = $retryTimes;
        } else {
            $this->retryTimes = 1;
        }
        $this->logFile = $logFile . date('Ymd');
    }
    
    /**
     * 获取推送对象
     * 
     * 
     * @return 
     * @author 张泽强<zhangzeqiang@eelly.net>
     * @since  2016年5月30日
     */
    public function push() {
        return new \PushPayload($this);
    }

    /**
     * 获取统计对象
     * 
     * 
     * @return 
     * @author 张泽强<zhangzeqiang@eelly.net>
     * @since  2016年5月30日
     */
    public function report() {
        return new \ReportPayload($this);
    }

    /**
     * 对象
     * 
     * 
     * @return \Swallow\Toolkit\Net\Push\core\DevicePayload
     * @author 张泽强<zhangzeqiang@eelly.net>
     * @since  2016年5月30日
     */
    public function device() {
        return new \DevicePayload($this);
    }

    /**
     * 获取定时推送对象
     * 
     * 
     * @return \Swallow\Toolkit\Net\Push\core\SchedulePayload
     * @author 张泽强<zhangzeqiang@eelly.net>
     * @since  2016年5月30日
     */
    public function schedule() {
        return new \SchedulePayload($this);
    }


    /**
     * 发送HTTP请求
     * @param $url string 请求的URL
     * @param $method int 请求的方法
     * @param null $body String POST请求的Body
     * @param int $times 当前重试的册数
     * @return array
     * /--------key---------|----------value----------
     *         errorCode           错误码，请求正常无此健
     *         body                    错误时为错误信息，请求正常时为接收到的数据
     *        headers
     *        http_code
     * @throws APIConnectionException
     */
    public function _request($url, $method, $body=null, $times=1) {
        $this->log("Send " . $method . " " . $url . ", body:" . $body . ", times:" . $times);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        // 设置User-Agent
        curl_setopt($ch, CURLOPT_USERAGENT, self::USER_AGENT);
        // 连接建立最长耗时
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::CONNECT_TIMEOUT);
        // 请求最长耗时
        curl_setopt($ch, CURLOPT_TIMEOUT, self::READ_TIMEOUT);
        // 设置SSL版本 1=CURL_SSLVERSION_TLSv1, 不指定使用默认值,curl会自动获取需要使用的CURL版本
        // curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // 如果报证书相关失败,可以考虑取消注释掉该行,强制指定证书版本
        //curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'TLSv1');
        // 设置Basic认证
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $this->appKey . ":" . $this->masterSecret);
        // 设置Post参数
        if ($method === self::HTTP_POST) {
            curl_setopt($ch, CURLOPT_POST, true);
        } else if ($method === self::HTTP_DELETE || $method === self::HTTP_PUT) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }
        if (!is_null($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        // 设置headers
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Connection: Keep-Alive'
        ));

        // 执行请求
        $output = curl_exec($ch);
        // 解析Response
        $response = array();
        $errorCode = curl_errno($ch);
        if ($errorCode) {
            $response['http_code'] = 500;
            if ($errorCode === 28) {
                $response['errorCode'] = 28;
                $response['body'] = "Response timeout. Your request has probably be received by JPush Server,please check that whether need to be pushed again";
            } else if ($errorCode === 56) {
                $response['errorCode'] = 56;
                $response['body'] = "Response timeout, maybe cause by old CURL version. Your request has probably be received by JPush Server, please check that whether need to be pushed again";
            } else if ($times >= $this->retryTimes) {
                $response['errorCode'] = 56;
                $response['body'] = "Connect timeout. Please retry later. Error:" . $errorCode . " " . curl_error($ch) . "Send " . $method . " " . $url . " fail, curl_code:" . $errorCode . ", body:" . $body . ", times:" . $times ;
            } else {
                $this->_request($url, $method, $body, ++$times);
            }
        } else {
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header_text = substr($output, 0, $header_size);
            $body = substr($output, $header_size);
            $headers = array();
            foreach (explode("\r\n", $header_text) as $i => $line) {
                if (!empty($line)) {
                    if ($i === 0) {
                        $headers['http_code'] = $line;
                    } else if (strpos($line, ": ")) {
                        list ($key, $value) = explode(': ', $line);
                        $headers[$key] = $value;
                    }
                }
            }
            $response['headers'] = $headers;
            $response['body'] = $body;
            $response['http_code'] = $httpCode;
        }
        curl_close($ch);
        return $response;
    }

    /**
     * 记录日志
     * 
     * 
     * @param unknown $content
     * @author 张泽强<zhangzeqiang@eelly.net>
     * @since  2016年5月30日
     */
    public function log($content) {
        if (!is_null($this->logFile)) {
//             error_log($content . "\r\n", 3, $this->logFile);
            $this->putFileLog([$content], $this->logFile);
        }
    }

    private function putFileLog($message, $loggerName)
    {
        static $logger = [];
        if (!isset($logger[$loggerName])) {
            $logger[$loggerName] = new \Monolog\Logger($loggerName);
            $logger[$loggerName]->pushHandler(new \Monolog\Handler\StreamHandler(LOG_PATH.'/'.$loggerName.'.'.date('Ymd').'.txt', \Monolog\Logger::INFO));
        }
        $msg = $_SERVER['REQUEST_URI'];

        return $logger[$loggerName]->info($msg, (array) $message);
    }
}