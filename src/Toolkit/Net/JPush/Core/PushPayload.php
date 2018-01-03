<?php

use Swallow\Toolkit\Net\JPush\JPush;
use Swallow\Exception\LogicException;
use Swallow\Exception\StatusCodeInfo;
use Swallow\Exception\StatusCode;
/**
 *  极光推送--推送消息
 * @author  zhangzeqiang <zhangzeqiang@eelly.com>
 */
class PushPayload {
    private static $EFFECTIVE_DEVICE_TYPES = array('ios', 'android', 'winphone');
    private static $LIMIT_KEYS = array('X-Rate-Limit-Limit'=>'rateLimitLimit', 'X-Rate-Limit-Remaining'=>'rateLimitRemaining', 'X-Rate-Limit-Reset'=>'rateLimitReset');
    const PUSH_URL = 'https://api.jpush.cn/v3/push';
    const PUSH_VALIDATE_URL = 'https://api.jpush.cn/v3/push/validate';
    private $client;
    private $platform;

    private $audience;
    private $tags;
    private $tagAnds;
    private $alias;
    private $registrationIds;

    private $notificationAlert;
    private $iosNotification;
    private $androidNotification;
    private $winPhoneNotification;
    private $smsMessage;
    private $message;
    private $options;
    
    /**
     * PushPayload constructor.
     * @param $client JPush
     */
    function __construct($client) {
        $this->client = $client;
    }
    
    /**
     * 设置平台
     * 
     * @param unknown $platform
     * @throws InvalidArgumentException
     * @return \Swallow\Toolkit\Net\Push\PushPayload
     * @author 张泽强<zhangzeqiang@eelly.net>
     * @since  2016年5月30日
     */
    public function setPlatform($platform) {
        if (is_string($platform) && strcasecmp("all", $platform) === 0) {
            $this->platform = "all";
        } else {
            if (!is_array($platform)) {
                $platform = func_get_args();
                if (count($platform) <= 0) {
                    throw new LogicException( "Missing argument for PushPayload::setPlatform()", StatusCode::INVALID_ARGUMENT);
                }
            }

            $_platform = array();
            foreach($platform as $type) {
                $type = strtolower($type);
                if (!in_array($type, self::$EFFECTIVE_DEVICE_TYPES)) {
                    throw new LogicException( "Invalid device type: ' $type'", StatusCode::INVALID_ARGUMENT);
                }
                if (!in_array($type, $_platform)) {
                    array_push($_platform, $type);
                }
            }
            $this->platform = $_platform;
        }
        return $this;
    }
    
    /**
     * 推送设备对象，表示一条推送可以被推送到哪些设备列表。确认推送设备对象，JPush 提供了多种方式，比如：别名、标签、注册ID、分群、广播等。
     * 
     * 
     * @param unknown $all
     * @throws InvalidArgumentException
     * @author 张泽强<zhangzeqiang@eelly.net>
     * @since  2016年5月30日
     */
    public function setAudience($all) {
        if (strtolower($all) === 'all') {
            $this->addAllAudience();
        } else {
            throw new LogicException( "Invalid audience value", StatusCode::INVALID_ARGUMENT);
        }
    }
    
    /**
     * 推送设备对象，表示一条推送可以被推送到哪些设备列表。确认推送设备对象，JPush 提供了多种方式，比如：别名、标签、注册ID、分群、广播等。
     * 
     * 
     * @return \Swallow\Toolkit\Net\Push\PushPayload
     * @author 张泽强<zhangzeqiang@eelly.net>
     * @since  2016年5月30日
     */
    public function addAllAudience() {
        $this->audience = "all";
        return $this;
    }

    /**
     * 添加标签
     * 
     * @param unknown $tag
     * @throws InvalidArgumentException
     * @return \Swallow\Toolkit\Net\Push\PushPayload
     * @author 张泽强<zhangzeqiang@eelly.net>
     * @since  2016年5月30日
     */
    public function addTag($tag) {
        if (is_null($this->tags)) {
            $this->tags = array();
        }

        if (is_array($tag)) {
            foreach($tag as $_tag) {
                if (!is_string($_tag)) {
                    throw new LogicException( "Invalid tag value", StatusCode::INVALID_ARGUMENT);
                }
                if (!in_array($_tag, $this->tags)) {
                    array_push($this->tags, $_tag);
                }
            }
        } else if (is_string($tag)) {
            if (!in_array($tag, $this->tags)) {
                array_push($this->tags, $tag);
            }
        } else {
            throw new LogicException( "IInvalid tag value", StatusCode::INVALID_ARGUMENT);
        }

        return $this;

    }
    
    /**
     * 
     * 
     * 
     * @param unknown $tag
     * @throws InvalidArgumentException
     * @return \Swallow\Toolkit\Net\Push\PushPayload
     * @author 张泽强<zhangzeqiang@eelly.net>
     * @since  2016年5月30日
     */
    public function addTagAnd($tag) {
        if (is_null($this->tagAnds)) {
            $this->tagAnds = array();
        }

        if (is_array($tag)) {
            foreach($tag as $_tag) {
                if (!is_string($_tag)) {
                    throw new LogicException( "Invalid tag_and value", StatusCode::INVALID_ARGUMENT);
                }
                if (!in_array($_tag, $this->tagAnds)) {
                    array_push($this->tagAnds, $_tag);
                }
            }
        } else if (is_string($tag)) {
            if (!in_array($tag, $this->tagAnds)) {
                array_push($this->tagAnds, $tag);
            }
        } else {
            throw new LogicException( "Invalid tag_and value", StatusCode::INVALID_ARGUMENT);
        }

        return $this;
    }
    
    /**
     * 增加别名
     * 
     * 
     * @param unknown $alias
     * @throws InvalidArgumentException
     * @return \Swallow\Toolkit\Net\Push\PushPayload
     * @author 张泽强<zhangzeqiang@eelly.net>
     * @since  2016年5月30日
     */
    public function addAlias($alias) {
        if (is_null($this->alias)) {
            $this->alias = array();
        }

        if (is_array($alias)) {
            foreach($alias as $_alias) {
                if (!is_string($_alias)) {
                    throw new LogicException( "Invalid alias value", StatusCode::INVALID_ARGUMENT);
                }
                if (!in_array($_alias, $this->alias)) {
                    array_push($this->alias, $_alias);
                }
            }
        } else if (is_string($alias)) {
            if (!in_array($alias, $this->alias)) {
                array_push($this->alias, $alias);
            }
        } else {
            throw new LogicException( "Invalid alias value", StatusCode::INVALID_ARGUMENT);
        }

        return $this;
    }
    
    /**
     * 设备标识。一次推送最多 1000 个。
     * 
     * 
     * @param unknown $registrationId
     * @throws InvalidArgumentException
     * @return \Swallow\Toolkit\Net\Push\PushPayload
     * @author 张泽强<zhangzeqiang@eelly.net>
     * @since  2016年5月30日
     */
    public function addRegistrationId($registrationId) {
        if (is_null($this->registrationIds)) {
            $this->registrationIds = array();
        }

        if (is_array($registrationId)) {
            foreach($registrationId as $_registrationId) {
                if (!is_string($_registrationId)) {
                    throw new LogicException( "Invalid registration_id value", StatusCode::INVALID_ARGUMENT);
                }
                if (!in_array($_registrationId, $this->registrationIds)) {
                    array_push($this->registrationIds, $_registrationId);
                }
            }
        } else if (is_string($registrationId)) {
            if (!in_array($registrationId, $this->registrationIds)) {
                array_push($this->registrationIds, $registrationId);
            }
        } else {
            throw new LogicException( "Invalid registration_id value", StatusCode::INVALID_ARGUMENT);
        }

        return $this;
    }

    /**
     * 通知(包含全平台)
     * 
     * 
     * @param unknown $alert
     * @throws InvalidArgumentException
     * @return \Swallow\Toolkit\Net\Push\PushPayload
     * @author 张泽强<zhangzeqiang@eelly.net>
     * @since  2016年5月30日
     */
    public function setNotificationAlert($alert) {
        if (!is_string($alert)) {
            throw new LogicException( "Invalid alert value", StatusCode::INVALID_ARGUMENT);
        }
        $this->notificationAlert = $alert;
        return $this;
    }

    /**
     * ios 通知
     * 
     * 
     * @param string $alert
     * @param string $sound
     * @param string $badge
     * @param string $content_available
     * @param string $category
     * @param string $extras
     * @throws InvalidArgumentException
     * @return \Swallow\Toolkit\Net\Push\PushPayload
     * @author 张泽强<zhangzeqiang@eelly.net>
     * @since  2016年5月30日
     */
    public function addIosNotification($alert=null, $sound=null, $badge=null, $content_available=null, $category=null, $extras=null) {
        $ios = array();

        if (!is_null($alert)) {
            if (!is_string($alert) && !is_array($alert)) {
                throw new LogicException( "Invalid ios alert value", StatusCode::INVALID_ARGUMENT);
                
            }
            $ios['alert'] = $alert;
        }

        if (!is_null($sound)) {
            if (!is_string($sound)) {
                throw new LogicException( "Invalid ios sound value", StatusCode::INVALID_ARGUMENT);
            }
            if ($sound !== JPush::DISABLE_SOUND) {
                $ios['sound'] = $sound;
            }
        } else {
            // 默认sound为''
            $ios['sound'] = '';
        }

        if (!is_null($badge)) {
            if (is_string($badge) && !preg_match("/^[+-]{1}[0-9]{1,3}$/", $badge)) {
                if (!is_int($badge)) {
                    throw new LogicException( "Invalid ios badge value", StatusCode::INVALID_ARGUMENT);
                }
            }
            if ($badge != JPush::DISABLE_BADGE) {
                $ios['badge'] = $badge;
            }
        } else {
            // 默认badge为'+1'
            $ios['badge'] = '+1';
        }

        if (!is_null($content_available)) {
            if (!is_bool($content_available)) {
                throw new LogicException( "Invalid ios content-available value", StatusCode::INVALID_ARGUMENT);
            }
            $ios['content-available'] = $content_available;
        }

        if (!is_null($category)) {
            if (!is_string($category)) {
                throw new LogicException( "Invalid ios category value", StatusCode::INVALID_ARGUMENT);
            }
            if (strlen($category)) {
                $ios['category'] = $category;
            }
        }

        if (!is_null($extras)) {
            if (!is_array($extras)) {
                throw new LogicException( "Invalid ios extras value", StatusCode::INVALID_ARGUMENT);
            }
            if (count($extras) > 0) {
                $ios['extras'] = $extras;
            }
        }

        if (count($ios) <= 0) {
            throw new LogicException( "Invalid iOS notification", StatusCode::INVALID_ARGUMENT);
        }

        $this->iosNotification = $ios;
        return $this;
    }

    /**
     * android 通知
     * 
     * 
     * @param string $alert
     * @param string $title
     * @param string $builderId
     * @param string $extras
     * @throws InvalidArgumentException
     * @return \Swallow\Toolkit\Net\Push\PushPayload
     * @author 张泽强<zhangzeqiang@eelly.net>
     * @since  2016年5月30日
     */
    public function addAndroidNotification($alert=null, $title=null, $builderId=null, $extras=null) {
        $android = array();

        if (!is_null($alert)) {
            if (!is_string($alert)) {
                throw new LogicException( "Invalid android alert value", StatusCode::INVALID_ARGUMENT);
            }
            $android['alert'] = $alert;
        }

        if (!is_null($title)) {
            if(!is_string($title)) {
                throw new LogicException( "Invalid android title value", StatusCode::INVALID_ARGUMENT);
            }
            if(strlen($title) > 0) {
                $android['title'] = $title;
            }
        }

        if (!is_null($builderId)) {
            if (!is_int($builderId)) {
                throw new LogicException( "Invalid android builder_id value", StatusCode::INVALID_ARGUMENT);
            }
            $android['builder_id'] = $builderId;
        }

        if (!is_null($extras)) {
            if (!is_array($extras)) {
                throw new LogicException( "Invalid android extras value", StatusCode::INVALID_ARGUMENT);
            }
            if (count($extras) > 0) {
                $android['extras'] = $extras;
            }
        }

        if (count($android) <= 0) {
            throw new LogicException( "Invalid android notification", StatusCode::INVALID_ARGUMENT);
        }

        $this->androidNotification = $android;
        return $this;
    }

    /**
     * winPhone通知
     * 
     * 
     * @param string $alert
     * @param string $title
     * @param string $_open_page
     * @param string $extras
     * @throws InvalidArgumentException
     * @return \Swallow\Toolkit\Net\Push\PushPayload
     * @author 张泽强<zhangzeqiang@eelly.net>
     * @since  2016年5月30日
     */
    public function addWinPhoneNotification($alert=null, $title=null, $_open_page=null, $extras=null) {
        $winPhone = array();

        if (!is_null($alert)) {
            if (!is_string($alert)) {
                throw new LogicException( "Invalid winphone notification", StatusCode::INVALID_ARGUMENT);
            }
            $winPhone['alert'] = $alert;
        }

        if (!is_null($title)) {
            if (!is_string($title)) {
                throw new LogicException( "Invalid winphone title notification", StatusCode::INVALID_ARGUMENT);
            }
            if(strlen($title) > 0) {
                $winPhone['title'] = $title;
            }
        }

        if (!is_null($_open_page)) {
            if (!is_string($_open_page)) {
                throw new LogicException( "Invalid winphone _open_page notification", StatusCode::INVALID_ARGUMENT);
            }
            if (strlen($_open_page) > 0) {
                $winPhone['_open_page'] = $_open_page;
            }
        }

        if (!is_null($extras)) {
            if (!is_array($extras)) {
                throw new LogicException( "Invalid winphone extras notification", StatusCode::INVALID_ARGUMENT);
            }
            if (count($extras) > 0) {
                $winPhone['extras'] = $extras;
            }
        }

        if (count($winPhone) <= 0) {
            throw new LogicException( "Invalid winphone notification", StatusCode::INVALID_ARGUMENT);
        }

        $this->winPhoneNotification = $winPhone;
        return $this;
    }

    /**
     * 发送短信(会产生额外的运营商费用)
     * 
     * 
     * @param unknown $content
     * @param unknown $delay_time
     * @throws InvalidArgumentException
     * @return \Swallow\Toolkit\Net\Push\PushPayload
     * @author 张泽强<zhangzeqiang@eelly.net>
     * @since  2016年5月30日
     */
    public function setSmsMessage($content, $delay_time) {
        $sms = array();
        if (is_null($content) || !is_string($content) || strlen($content) < 0 || strlen($content) > 480) {
            throw new LogicException( "Invalid sms content, sms content\'s length must in [0, 480]'", StatusCode::INVALID_ARGUMENT);
        } else {
            $sms['content'] = $content;
        }

        if (is_null($delay_time) || !is_int($delay_time) || $delay_time < 0 || $delay_time > 86400) {
            throw new LogicException( "Invalid sms delay time, delay time must in [0, 86400]", StatusCode::INVALID_ARGUMENT);
        } else {
            $sms['delay_time'] = $delay_time;
        }

        $this->smsMessage = $sms;
        return $this;
    }

    /**
     * 设置自定义消息(透传)
     * 
     * 
     * @param unknown $msg_content
     * @param string $title
     * @param string $content_type
     * @param string $extras
     * @throws InvalidArgumentException
     * @return \Swallow\Toolkit\Net\Push\PushPayload
     * @author 张泽强<zhangzeqiang@eelly.net>
     * @since  2016年5月30日
     */
    public function setMessage($msg_content, $title=null, $content_type=null, $extras=null) {
        $message = array();
        if (is_null($msg_content) || !is_string($msg_content)) {
            throw new LogicException( "Invalid message content", StatusCode::INVALID_ARGUMENT);
        } else {
            $message['msg_content'] = $msg_content;
        }

        if (!is_null($title)) {
            if (!is_string($title)) {
                throw new LogicException( "Invalid message title", StatusCode::INVALID_ARGUMENT);
            }
            $message['title'] = $title;
        }

        if (!is_null($content_type)) {
            if (!is_string($content_type)) {
                throw new LogicException( "Invalid message content type", StatusCode::INVALID_ARGUMENT);
            }
            $message["content_type"] = $content_type;
        }

        if (!is_null($extras)) {
            if (!is_array($extras)) {
                throw new LogicException( "Invalid message extras", StatusCode::INVALID_ARGUMENT);
            }
            if (count($extras) > 0) {
                $message['extras'] = $extras;
            }
        }

        $this->message = $message;
        return $this;
    }
    
    /**
     * 推送可选项
     * 
     * @param int $sendno  推送序号
     *      纯粹用来作为 API 调用标识，API 返回时被原样返回，以方便 API 调用方匹配请求与返回。
     * @param string $time_to_live 离线消息保留时长(秒) 默认1天，最大10天，可设为0
     * @param string $override_msg_id 要覆盖的消息ID
     * @param string $apns_production APNs是否生产环境  True :生产环境(默认)，False:开发环境
     * @param string $big_push_duration 定速推送时长(分钟) 最大值为1400
     * @throws InvalidArgumentException
     * @return PushPayload
     * @since  2016年5月27日
     */
    public function setOptions($sendno=null, $time_to_live=null, $override_msg_id=null, $apns_production=null, $big_push_duration=null) {
        $options = array();

        if (!is_null($sendno)) {
            if (!is_int($sendno)) {
                throw new LogicException( "Invalid option sendno", StatusCode::INVALID_ARGUMENT);
            }
            $options['sendno'] = $sendno;
        } else {
            $options['sendno'] = $this->generateSendno();
        }

        if (!is_null($time_to_live)) {
            if (!is_int($time_to_live) || $time_to_live < 0 || $time_to_live > 864000) {
                throw new LogicException( "Invalid option time to live, it must be a int and in [0, 864000]", StatusCode::INVALID_ARGUMENT);
            }
            $options['time_to_live'] = $time_to_live;
        }

        if (!is_null($override_msg_id)) {
            if (!is_long($override_msg_id)) {
                throw new LogicException( "Invalid option override msg id", StatusCode::INVALID_ARGUMENT);
            }
            $options['override_msg_id'] = $override_msg_id;
        }

        if (!is_null($apns_production)) {
            if (!is_bool($apns_production)) {
                throw new LogicException( "Invalid option apns production", StatusCode::INVALID_ARGUMENT);
            }
            $options['apns_production'] = $apns_production;
        } else {
            $options['apns_production'] = false;
        }

        if (!is_null($big_push_duration)) {
            if (!is_int($big_push_duration) || $big_push_duration < 0 || $big_push_duration > 1440) {
                throw new LogicException( "Invalid option big push duration, it must be a int and in [0, 1440]", StatusCode::INVALID_ARGUMENT);
            }
            $options['big_push_duration'] = $big_push_duration;
        }

        $this->options = $options;
        return $this;
    }

    /**
     * 
     * 
     * 
     * @throws InvalidArgumentException
     * @return multitype:NULL unknown multitype:NULL  
     * @author 张泽强<zhangzeqiang@eelly.net>
     * @since  2016年5月30日
     */
    public function build() {
        $payload = array();

        // validate platform
        if (is_null($this->platform)) {
            throw new LogicException( "platform must be set", StatusCode::INVALID_ARGUMENT);
        }
        $payload["platform"] = $this->platform;

        // validate audience
        $audience = array();
        if (!is_null($this->tags)) {
            $audience["tag"] = $this->tags;
        }
        if (!is_null($this->tagAnds)) {
            $audience["tag_and"] = $this->tagAnds;
        }
        if (!is_null($this->alias)) {
            $audience["alias"] = $this->alias;
        }
        if (!is_null($this->registrationIds)) {
            $audience["registration_id"] = $this->registrationIds;
        }

        if (is_null($this->audience) && count($audience) <= 0) {
            throw new LogicException( "audience must be set", StatusCode::INVALID_ARGUMENT);
        } else if (!is_null($this->audience) && count($audience) > 0) {
            throw new LogicException( "you can't add tags/alias/registration_id/tag_and when audience='all'", StatusCode::INVALID_ARGUMENT);
        } else if (is_null($this->audience)) {
            $payload["audience"] = $audience;
        } else {
            $payload["audience"] = $this->audience;
        }


        // validate notification
        $notification = array();

        if (!is_null($this->notificationAlert)) {
            $notification['alert'] = $this->notificationAlert;
        }

        if (!is_null($this->androidNotification)) {
            $notification['android'] = $this->androidNotification;
            if (is_null($this->androidNotification['alert'])) {
                if (is_null($this->notificationAlert)) {
                    throw new LogicException( "Android alert can not be null", StatusCode::INVALID_ARGUMENT);
                } else {
                    $notification['android']['alert'] = $this->notificationAlert;
                }
            }
        }

        if (!is_null($this->iosNotification)) {
            $notification['ios'] = $this->iosNotification;
            if (is_null($this->iosNotification['alert'])) {
                if (is_null($this->notificationAlert)) {
                    throw new LogicException( "iOS alert can not be null", StatusCode::INVALID_ARGUMENT);
                } else {
                    $notification['ios']['alert'] = $this->notificationAlert;
                }
            }
        }

        if (!is_null($this->winPhoneNotification)) {
            $notification['winphone'] = $this->winPhoneNotification;
            if (is_null($this->winPhoneNotification['alert'])) {
                if (is_null($this->winPhoneNotification)) {
                    throw new LogicException( "WinPhone alert can not be null", StatusCode::INVALID_ARGUMENT);
                } else {
                    $notification['winphone']['alert'] = $this->notificationAlert;
                }
            }
        }

        if (count($notification) > 0) {
            $payload['notification'] = $notification;
        }

        if (count($this->message) > 0) {
            $payload['message'] = $this->message;
        }
        if (!array_key_exists('notification', $payload) && !array_key_exists('message', $payload)) {
            throw new LogicException( "notification and message can not all be null", StatusCode::INVALID_ARGUMENT);
        }

        if (count($this->smsMessage)) {
            $payload['sms_message'] = $this->smsMessage;
        }

        if (count($this->options) > 0) {
            $payload['options'] = $this->options;
        } else {
            $this->setOptions();
            $payload['options'] = $this->options;
        }
        return $payload;
    }

    /**
     * 转化
     * 
     * @return string
     * @author 张泽强<zhangzeqiang@eelly.net>
     * @since  2016年5月30日
     */
    public function toJSON() {
        $payload = $this->build();
        return json_encode($payload);
    }

    /**
     * 
     * @return \Swallow\Toolkit\Net\Push\PushPayload
     * @author 张泽强<zhangzeqiang@eelly.net>
     * @since  2016年5月30日
     */
    public function printJSON() {
        echo $this->toJSON();
        return $this;
    }

    /**
     * 发送请求
     * 
     * @return Ambigous <multitype:, multitype:mixed multitype:unknown  >
     * @author 张泽强<zhangzeqiang@eelly.net>
     * @since  2016年5月30日
     */
    public function send() {
        $response = $this->client->_request(PushPayload::PUSH_URL, JPush::HTTP_POST, $this->toJSON());
        return $this->__processResp($response);
    }

    /**
     * 
     * @author 张泽强<zhangzeqiang@eelly.net>
     * @since  2016年5月30日
     */
    public function validate() {
        $response = $this->client->_request(PushPayload::PUSH_VALIDATE_URL, JPush::HTTP_POST, $this->toJSON());
        return $this->__processResp($response);
    }
    
    /**
     * 
     * @param unknown $response
     * @throws APIRequestException
     * @return multitype:mixed multitype:unknown  
     * @author 张泽强<zhangzeqiang@eelly.net>
     * @since  2016年5月30日
     */
    private function __processResp($response) {
        if (!empty($response['errorCode'])) {
            return ['errorCode' => $response['errorCode'], 'errorMsg' => $response['body']];
        }
        
        if($response['http_code'] === 200) {
            $body = array();
            $body['data'] = json_decode($response['body'], true);
            $headers = $response['headers'];
            if (is_array($headers)) {
                $limit = array();
                foreach (self::$LIMIT_KEYS as $key => $value) {
                    if (array_key_exists($key, $headers)) {
                        $limit[$value] = $headers[$key];
                    }
                }
                if (count($limit) > 0) {
                    $body['limit'] = $limit;
                }
                return $body;
            }
            return $body;
        } else {
//             throw new APIRequestException($response);
                //记录错误信息
             return ['errorCode' => $response['http_code'], 'errorMsg' => $response['body']];
        }
    }
    
    /**
     * 
     * 
     * 
     * @return number
     * @author 张泽强<zhangzeqiang@eelly.net>
     * @since  2016年5月30日
     */
    private function generateSendno() {
        return rand(100000, 4294967294);
    }

}