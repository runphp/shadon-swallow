<?php

use Swallow\Toolkit\Net\JPush\JPush;
use Swallow\Exception\LogicException;
use Swallow\Exception\StatusCodeInfo;
use Swallow\Exception\StatusCode;

/**
 *  极光推送--Received API 以 msg_id 作为参数，去获取该 msg_id 的送达统计数据
 * @author  zhangzeqiang <zhangzeqiang@eelly.com>
 */
class ReportPayload {
    private static $EFFECTIVE_TIME_UNIT = array('HOUR', 'DAY', 'MONTH');
    private static $LIMIT_KEYS = array('X-Rate-Limit-Limit'=>'rateLimitLimit', 'X-Rate-Limit-Remaining'=>'rateLimitRemaining', 'X-Rate-Limit-Reset'=>'rateLimitReset');
    const REPORT_URL = 'https://report.jpush.cn/v3/received';
    const MESSAGES_URL = 'https://report.jpush.cn/v3/messages';
    const USERS_URL = 'https://report.jpush.cn/v3/users';

    private $client;

    /**
     * ReportPayload constructor.
     * @param $client JPush
     */
    public function __construct($client)
    {
        $this->client = $client;
    }

    /**
     * 
     * 
     * 
     * @param unknown $msgIds
     * @throws InvalidArgumentException
     * @return Ambigous <StdClass, multitype:array StdClass >
     * @author 张泽强<zhangzeqiang@eelly.net>
     * @since  2016年5月30日
     */
    public function getReceived($msgIds) {
        $queryParams = '?msg_ids=';
        if (is_array($msgIds) && count($msgIds) > 0) {
            $isFirst = true;
            foreach ($msgIds as $msgId) {
                if ($isFirst) {
                    $queryParams .= $msgId;
                    $isFirst = false;
                } else {
                    $queryParams .= ',';
                    $queryParams .= $msgId;
                }
            }
        } else if (is_string($msgIds)) {
            $queryParams .= $msgIds;
        } else {
            throw new LogicException( "Invalid msg_ids)", StatusCode::INVALID_ARGUMENT);
        }

        $url = ReportPayload::REPORT_URL . $queryParams;
        return $this->__request($url);
    }
    
    /**
     * 
     * 
     * 
     * @param unknown $msgIds
     * @throws InvalidArgumentException
     * @return Ambigous <StdClass, multitype:array StdClass >
     * @author 张泽强<zhangzeqiang@eelly.net>
     * @since  2016年5月30日
     */
    public function getMessages($msgIds) {
        $queryParams = '?msg_ids=';
        if (is_array($msgIds) && count($msgIds) > 0) {
            $isFirst = true;
            foreach ($msgIds as $msgId) {
                if ($isFirst) {
                    $queryParams .= $msgId;
                    $isFirst = false;
                } else {
                    $queryParams .= ',';
                    $queryParams .= $msgId;
                }
            }
        } else if (is_string($msgIds)) {
            $queryParams .= $msgIds;
        } else {
            throw new LogicException( "Invalid msg_ids", StatusCode::INVALID_ARGUMENT);
        }

        $url = ReportPayload::MESSAGES_URL . $queryParams;
        return $this->__request($url);
    }
    
    /**
     * 
     * 
     * 
     * @param unknown $time_unit
     * @param unknown $start
     * @param unknown $duration
     * @throws InvalidArgumentException
     * @return Ambigous <StdClass, multitype:array StdClass >
     * @author 张泽强<zhangzeqiang@eelly.net>
     * @since  2016年5月30日
     */
    public function getUsers($time_unit, $start, $duration) {
        $time_unit = strtoupper($time_unit);
        if (!in_array($time_unit, self::$EFFECTIVE_TIME_UNIT)) {
            throw new LogicException( "Invalid time unit", StatusCode::INVALID_ARGUMENT);
        }

        $url = ReportPayload::USERS_URL . '?time_unit=' . $time_unit . '&start=' . $start . '&duration=' . $duration;
        return $this->__request($url);
    }
    
    /**
     * 
     * 
     * 
     * @param unknown $url
     * @throws APIRequestException
     * @return StdClass|multitype:array StdClass 
     * @author 张泽强<zhangzeqiang@eelly.net>
     * @since  2016年5月30日
     */
    private function __request($url) {
        $response = $this->client->_request($url, JPush::HTTP_GET);
        if($response['http_code'] === 200) {
            $body = array();
            $body['data'] = (array)json_decode($response['body']);
            $headers = $response['headers'];

            if (is_array($headers)) {
                $limit = array();
                foreach (self::$LIMIT_KEYS as $key => $value) {
                    if (array_key_exists($key, $headers)) {
                        $limit[$value] = $headers[$key];
                    }
                }
                if (count($limit) > 0) {
                    $body['limit'] = (object)$limit;
                }
                return (object)$body;
            }
            return $body;
        } else {
            throw new APIRequestException($response);
            //记录错误信息
//             return ['errorCode' => $response['http_code'], 'errorMsg' => $response['body']];
        }
    }
}