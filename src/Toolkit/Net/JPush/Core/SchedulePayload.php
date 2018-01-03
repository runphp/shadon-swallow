<?php

use Swallow\Toolkit\Net\JPush\JPush;
use Swallow\Exception\LogicException;
use Swallow\Exception\StatusCodeInfo;
use Swallow\Exception\StatusCode;


/**
 *  极光推送--API 层面支持定时推送功能。
 * @author  zhangzeqiang <zhangzeqiang@eelly.com>
 */
class SchedulePayload {
    private static $LIMIT_KEYS = array('X-Rate-Limit-Limit'=>'rateLimitLimit', 'X-Rate-Limit-Remaining'=>'rateLimitRemaining', 'X-Rate-Limit-Reset'=>'rateLimitReset');

    const SCHEDULES_URL = 'https://api.jpush.cn/v3/schedules';
    private $client;

    /**
     * SchedulePayload constructor.
     * @param $client JPush
     */
    public function __construct($client) {
        $this->client = $client;
    }

    /**
     * 
     * 
     * 
     * @param unknown $name
     * @param unknown $push_payload
     * @param unknown $trigger
     * @throws InvalidArgumentException
     * @return Ambigous <StdClass, multitype:mixed StdClass >
     * @author 张泽强<zhangzeqiang@eelly.net>
     * @since  2016年5月30日
     */
    public function createSingleSchedule($name, $push_payload, $trigger) {
        if (!is_string($name)) {
            throw new LogicException( "Invalid schedule name", StatusCode::INVALID_ARGUMENT);
        }
        if (!is_array($push_payload)) {
            throw new LogicException( "Invalid schedule push payload", StatusCode::INVALID_ARGUMENT);
        }
        if (!is_array($trigger)) {
            throw new LogicException( "Invalid schedule trigger", StatusCode::INVALID_ARGUMENT);
        }
        $payload = array();
        $payload['name'] = $name;
        $payload['enabled'] = true;
        $payload['trigger'] = array("single"=>$trigger);
        $payload['push'] = $push_payload;
        $response = $this->client->_request(SchedulePayload::SCHEDULES_URL, JPush::HTTP_POST, json_encode($payload));
        return $this->__processResp($response);
    }
    
    /**
     * 
     * 
     * 
     * @param unknown $name
     * @param unknown $push_payload
     * @param unknown $trigger
     * @throws InvalidArgumentException
     * @return Ambigous <StdClass, multitype:mixed StdClass >
     * @author 张泽强<zhangzeqiang@eelly.net>
     * @since  2016年5月30日
     */
    public function createPeriodicalSchedule($name, $push_payload, $trigger) {
        if (!is_string($name)) {
            throw new LogicException( "Invalid schedule name", StatusCode::INVALID_ARGUMENT);
        }
        if (!is_array($push_payload)) {
            throw new LogicException( "Invalid schedule push payload", StatusCode::INVALID_ARGUMENT);
        }
        if (!is_array($trigger)) {
            throw new LogicException( "Invalid schedule trigger", StatusCode::INVALID_ARGUMENT);
        }
        $payload = array();
        $payload['name'] = $name;
        $payload['enabled'] = true;
        $payload['trigger'] = array("periodical"=>$trigger);
        $payload['push'] = $push_payload;
        $response = $this->client->_request(SchedulePayload::SCHEDULES_URL, JPush::HTTP_POST, json_encode($payload));
        return $this->__processResp($response);
    }
    
    /**
     * 
     * 
     * 
     * @param unknown $schedule_id
     * @param string $name
     * @param string $enabled
     * @param string $push_payload
     * @param string $trigger
     * @throws InvalidArgumentException
     * @return Ambigous <StdClass, multitype:mixed StdClass >
     * @author 张泽强<zhangzeqiang@eelly.net>
     * @since  2016年5月30日
     */
    public function updateSingleSchedule($schedule_id, $name=null, $enabled=null, $push_payload=null, $trigger=null) {
        if (!is_string($schedule_id)) {
            throw new LogicException( "Invalid schedule id", StatusCode::INVALID_ARGUMENT);
        }
        $payload = array();
        if (!is_null($name)) {
            if (!is_string($name)) {
                throw new LogicException( "Invalid schedule name", StatusCode::INVALID_ARGUMENT);
            } else {
                $payload['name'] = $name;
            }
        }

        if (!is_null($enabled)) {
            if (!is_bool($enabled)) {
                throw new LogicException( "Invalid schedule enable", StatusCode::INVALID_ARGUMENT);
            } else {
                $payload['enabled'] = $enabled;
            }
        }

        if (!is_null($push_payload)) {
            if (!is_array($push_payload)) {
                throw new LogicException( "Invalid schedule push payload", StatusCode::INVALID_ARGUMENT);
            } else {
                $payload['push'] = $push_payload;
            }
        }

        if (!is_null($trigger)) {
            if (!is_array($trigger)) {
                throw new LogicException( "Invalid schedule trigger", StatusCode::INVALID_ARGUMENT);
            } else {
                $payload['trigger'] = array("single"=>$trigger);
            }
        }

        if (count($payload) <= 0) {
            throw new LogicException( "Invalid schedule, name, enabled, trigger, push can not all be null", StatusCode::INVALID_ARGUMENT);
        }

        $url = SchedulePayload::SCHEDULES_URL . "/" . $schedule_id;
        $response = $this->client->_request($url, JPush::HTTP_PUT, json_encode($payload));
        return $this->__processResp($response);
    }

    /**
     * 
     * 
     * 
     * @param unknown $schedule_id
     * @param string $name
     * @param string $enabled
     * @param string $push_payload
     * @param string $trigger
     * @throws InvalidArgumentException
     * @return Ambigous <StdClass, multitype:mixed StdClass >
     * @author 张泽强<zhangzeqiang@eelly.net>
     * @since  2016年5月30日
     */
    public function updatePeriodicalSchedule($schedule_id, $name=null, $enabled=null, $push_payload=null, $trigger=null) {
        if (!is_string($schedule_id)) {
            throw new LogicException( "Invalid schedule id", StatusCode::INVALID_ARGUMENT);
        }
        $payload = array();
        if (!is_null($name)) {
            if (!is_string($name)) {
                throw new LogicException( "Invalid schedule name", StatusCode::INVALID_ARGUMENT);
            } else {
                $payload['name'] = $name;
            }
        }

        if (!is_null($enabled)) {
            if (!is_bool($enabled)) {
                throw new LogicException( "Invalid schedule enable", StatusCode::INVALID_ARGUMENT);
            } else {
                $payload['enabled'] = $enabled;
            }
        }

        if (!is_null($push_payload)) {
            if (!is_array($push_payload)) {
                throw new LogicException( "Invalid schedule push payload", StatusCode::INVALID_ARGUMENT);
            } else {
                $payload['push'] = $push_payload;
            }
        }

        if (!is_null($trigger)) {
            if (!is_array($trigger)) {
                throw new LogicException( "Invalid schedule trigger", StatusCode::INVALID_ARGUMENT);
            } else {
                $payload['trigger'] = array("periodical"=>$trigger);
            }
        }

        if (count($payload) <= 0) {
            throw new LogicException( "Invalid schedule, name, enabled, trigger, push can not all be null", StatusCode::INVALID_ARGUMENT);
        }

        $url = SchedulePayload::SCHEDULES_URL . "/" . $schedule_id;
        $response = $this->client->_request($url, JPush::HTTP_PUT, json_encode($payload));
        return $this->__processResp($response);
    }

    /**
     * 
     * 
     * 
     * @param number $page
     * @throws InvalidArgumentException
     * @return Ambigous <StdClass, multitype:mixed StdClass >
     * @author 张泽强<zhangzeqiang@eelly.net>
     * @since  2016年5月30日
     */
    public function getSchedules($page=1) {
        if (!is_int($page)) {
            throw new LogicException( "Invalid pages", StatusCode::INVALID_ARGUMENT);
        }
        $url = SchedulePayload::SCHEDULES_URL . "?page=" . $page;
        $response = $this->client->_request($url, JPush::HTTP_GET);
        return $this->__processResp($response);
    }

    /**
     * 
     * 
     * 
     * @param unknown $schedule_id
     * @throws InvalidArgumentException
     * @return Ambigous <StdClass, multitype:mixed StdClass >
     * @author 张泽强<zhangzeqiang@eelly.net>
     * @since  2016年5月30日
     */
    public function getSchedule($schedule_id) {
        if (!is_string($schedule_id)) {
            throw new LogicException( "Invalid schedule id", StatusCode::INVALID_ARGUMENT);
        }
        $url = SchedulePayload::SCHEDULES_URL . "/" . $schedule_id;
        $response = $this->client->_request($url, JPush::HTTP_GET);
        return $this->__processResp($response);
    }

    /**
     * 
     * 
     * 
     * @param unknown $schedule_id
     * @throws InvalidArgumentException
     * @return Ambigous <StdClass, multitype:mixed StdClass >
     * @author 张泽强<zhangzeqiang@eelly.net>
     * @since  2016年5月30日
     */
    public function deleteSchedule($schedule_id) {
        if (!is_string($schedule_id)) {
            throw new LogicException( "Invalid schedule id", StatusCode::INVALID_ARGUMENT);
        }
        $url = SchedulePayload::SCHEDULES_URL . "/" . $schedule_id;
        $response = $this->client->_request($url, JPush::HTTP_DELETE);
        return $this->__processResp($response);
    }

    /**
     * 
     * 
     * 
     * @param unknown $response
     * @throws APIRequestException
     * @return StdClass|multitype:mixed StdClass 
     * @author 张泽强<zhangzeqiang@eelly.net>
     * @since  2016年5月30日
     */
    private function __processResp($response) {
        if($response['http_code'] === 200) {
            $body = array();
            $data = json_decode($response['body']);
            if (!is_null($data)) {
                $body['data'] = json_decode($response['body']);
            }
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
        }
    }
}

