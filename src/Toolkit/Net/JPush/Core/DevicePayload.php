<?php

use Swallow\Toolkit\Net\JPush\JPush;
use Swallow\Exception\LogicException;
use Swallow\Exception\StatusCodeInfo;
use Swallow\Exception\StatusCode;
/**
 *  极光推送--Device API 用于在服务器端查询、设置、更新、删除设备的 tag,alias 信息，使用时需要注意不要让服务端设置的标签又被客户端给覆盖了。
 * @author  zhangzeqiang <zhangzeqiang@eelly.com>
 */
class DevicePayload {
    private static $LIMIT_KEYS = array('X-Rate-Limit-Limit'=>'rateLimitLimit', 'X-Rate-Limit-Remaining'=>'rateLimitRemaining', 'X-Rate-Limit-Reset'=>'rateLimitReset');

    const DEVICE_URL = 'https://device.jpush.cn/v3/devices/';
    const DEVICE_STATUS_URL = 'https://device.jpush.cn/v3/devices/status/';
    const TAG_URL = 'https://device.jpush.cn/v3/tags/';
    const IS_IN_TAG_URL = 'https://device.jpush.cn/v3/tags/{tag}/registration_ids/{registration_id}';
    const ALIAS_URL = 'https://device.jpush.cn/v3/aliases/';


    private $client;

    /**
     * DevicePayload constructor.
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
     * @param unknown $registrationId
     * @return Ambigous <StdClass, multitype:mixed StdClass >
     * @author 张泽强<zhangzeqiang@eelly.net>
     * @since  2016年5月30日
     */
    public function getDevices($registrationId) {
        $url = DevicePayload::DEVICE_URL . $registrationId;
        $response = $this->client->_request($url, JPush::HTTP_GET);
        return $this->__processResp($response);
    }

    /**
     * 
     * 
     * 
     * @param unknown $registrationId
     * @param string $alias
     * @param string $mobile
     * @param string $addTags
     * @param string $removeTags
     * @throws InvalidArgumentException
     * @return Ambigous <StdClass, multitype:mixed StdClass >
     * @author 张泽强<zhangzeqiang@eelly.net>
     * @since  2016年5月30日
     */
    public function updateDevice($registrationId, $alias = null, $mobile=null, $addTags = null, $removeTags = null) {
        $payload = array();
        if (!is_string($registrationId)) {
            throw new LogicException( "Invalid registration_id", StatusCode::INVALID_ARGUMENT);
        }

        $aliasIsNull = is_null($alias);
        $mobileIsNull = is_null($mobile);
        $addTagsIsNull = is_null($addTags);
        $removeTagsIsNull = is_null($removeTags);


        if ($aliasIsNull && $addTagsIsNull && $removeTagsIsNull && $mobileIsNull) {
            throw new LogicException( "alias, addTags, removeTags not all null", StatusCode::INVALID_ARGUMENT);
        }

        if (!$aliasIsNull) {
            if (is_string($alias)) {
                $payload['alias'] = $alias;
            } else {
                throw new LogicException( "Invalid alias string", StatusCode::INVALID_ARGUMENT);
            }
        }

        if (!$mobileIsNull) {
            if (is_string($mobile)) {
                $payload['mobile'] = $mobile;
            } else {
                throw new LogicException( "Invalid mobile string", StatusCode::INVALID_ARGUMENT);
            }
        }

        $tags = array();

        if (!$addTagsIsNull) {
            if (is_array($addTags)) {
                $tags['add'] = $addTags;
            } else {
                throw new LogicException( "Invalid addTags array", StatusCode::INVALID_ARGUMENT);
            }
        }

        if (!$removeTagsIsNull) {
            if (is_array($removeTags)) {
                $tags['remove'] = $removeTags;
            } else {
                throw new LogicException( "Invalid removeTags array", StatusCode::INVALID_ARGUMENT);
            }
        }

        if (count($tags) > 0) {
            $payload['tags'] = $tags;
        }

        $url = DevicePayload::DEVICE_URL . $registrationId;
        $response = $this->client->_request($url, JPush::HTTP_POST, json_encode($payload));
        return $this->__processResp($response);
    }

    /**
     * 
     * 
     * 
     * @return Ambigous <StdClass, multitype:mixed StdClass >
     * @author 张泽强<zhangzeqiang@eelly.net>
     * @since  2016年5月30日
     */
    public function getTags() {
        $response = $this->client->_request(DevicePayload::TAG_URL, JPush::HTTP_GET);
        return $this->__processResp($response);
    }

    /**
     * 
     * 
     * 
     * @param unknown $registrationId
     * @param unknown $tag
     * @throws InvalidArgumentException
     * @return Ambigous <StdClass, multitype:mixed StdClass >
     * @author 张泽强<zhangzeqiang@eelly.net>
     * @since  2016年5月30日
     */
    public function isDeviceInTag($registrationId, $tag) {
        if (!is_string($registrationId)) {
            throw new LogicException( "Invalid registration_id", StatusCode::INVALID_ARGUMENT);
        }

        if (!is_string($tag)) {
            throw new LogicException( "Invalid tag", StatusCode::INVALID_ARGUMENT);
        }

        $url = str_replace('{tag}', $tag, self::IS_IN_TAG_URL);
        $url = str_replace('{registration_id}', $registrationId, $url);

        $response = $this->client->_request($url, JPush::HTTP_GET);
        return $this->__processResp($response);
    }

    /**
     * 
     * 
     * 
     * @param unknown $tag
     * @param string $addDevices
     * @param string $removeDevices
     * @throws InvalidArgumentException
     * @return Ambigous <StdClass, multitype:mixed StdClass >
     * @author 张泽强<zhangzeqiang@eelly.net>
     * @since  2016年5月30日
     */
    public function updateTag($tag, $addDevices = null, $removeDevices = null) {
        if (!is_string($tag)) {
            throw new LogicException( "Invalid tag", StatusCode::INVALID_ARGUMENT);
        }

        $addDevicesIsNull = is_null($addDevices);
        $removeDevicesIsNull = is_null($removeDevices);

        if ($addDevicesIsNull && $removeDevicesIsNull) {
            throw new LogicException( "Either or both addDevices and removeDevices must be set.", StatusCode::INVALID_ARGUMENT);
        }

        $registrationId = array();

        if (!$addDevicesIsNull) {
            if (is_array($addDevices)) {
                $registrationId['add'] = $addDevices;
            } else {
                throw new LogicException( "Invalid addDevices", StatusCode::INVALID_ARGUMENT);
            }
        }

        if (!$removeDevicesIsNull) {
            if (is_array($removeDevices)) {
                $registrationId['remove'] = $removeDevices;
            } else {
                throw new LogicException( "Invalid removeDevices", StatusCode::INVALID_ARGUMENT);
            }
        }

        $url = DevicePayload::TAG_URL . $tag;
        $payload = array('registration_ids'=>$registrationId);

        $response = $this->client->_request($url, JPush::HTTP_POST, json_encode($payload));
        return $this->__processResp($response);
    }

    /**
     * 
     * 
     * 
     * @param unknown $tag
     * @throws InvalidArgumentException
     * @return Ambigous <StdClass, multitype:mixed StdClass >
     * @author 张泽强<zhangzeqiang@eelly.net>
     * @since  2016年5月30日
     */
    public function deleteTag($tag) {
        if (!is_string($tag)) {
            throw new LogicException( "Invalid tag", StatusCode::INVALID_ARGUMENT);
        }
        $url = DevicePayload::TAG_URL . $tag;
        $response = $this->client->_request($url, JPush::HTTP_DELETE);
        return $this->__processResp($response);
    }

    /**
     * 
     * 
     * 
     * @param unknown $alias
     * @param string $platform
     * @throws InvalidArgumentException
     * @return Ambigous <StdClass, multitype:mixed StdClass >
     * @author 张泽强<zhangzeqiang@eelly.net>
     * @since  2016年5月30日
     */
    public function getAliasDevices($alias, $platform = null) {
        if (!is_string($alias)) {
            throw new LogicException( "Invalid alias", StatusCode::INVALID_ARGUMENT);
        }

        $url = self::ALIAS_URL . $alias;

        if (!is_null($platform)) {
            if (is_array($platform)) {
                $isFirst = true;
                foreach($platform as $item) {
                    if ($isFirst) {
                        $url = $url . '?platform=' . $item;
                        $isFirst = false;
                    } else {
                        $url = $url . ',' . $item;
                    }
                }
            } else if (is_string($platform)) {
                $url = $url . '?platform=' . $platform;
            } else {
                throw new LogicException( "Invalid platform", StatusCode::INVALID_ARGUMENT);
            }
        }

        $response = $this->client->_request($url, JPush::HTTP_GET);
        return $this->__processResp($response);
    }

    /**
     * 
     * 
     * 
     * @param unknown $alias
     * @throws InvalidArgumentException
     * @return Ambigous <StdClass, multitype:mixed StdClass >
     * @author 张泽强<zhangzeqiang@eelly.net>
     * @since  2016年5月30日
     */
    public function deleteAlias($alias) {
        if (!is_string($alias)) {
            throw new LogicException( "Invalid alias", StatusCode::INVALID_ARGUMENT);
        }
        $url = self::ALIAS_URL . $alias;
        $response = $this->client->_request($url, JPush::HTTP_DELETE);
        return $this->__processResp($response);
    }

    /**
     * 
     * 
     * 
     * @param unknown $registrationId
     * @throws InvalidArgumentException
     * @throws APIRequestException
     * @return StdClass|multitype:array StdClass 
     * @author 张泽强<zhangzeqiang@eelly.net>
     * @since  2016年5月30日
     */
    public function getDevicesStatus($registrationId) {
        if (!is_array($registrationId) && !is_string($registrationId)) {
            throw new LogicException( "Invalid registration_id", StatusCode::INVALID_ARGUMENT);
        }

        if (is_string($registrationId)) {
            $registrationId = explode(',', $registrationId);
        }

        $payload = array();
        if (count($registrationId) <= 0) {
            throw new LogicException( "Invalid registration_id", StatusCode::INVALID_ARGUMENT);
        }
        $payload['registration_ids'] = $registrationId;


        $response = $this->client->_request(DevicePayload::DEVICE_STATUS_URL, JPush::HTTP_POST, json_encode($payload));
        if($response['http_code'] === 200) {
            $body = array();
            echo $response['body'];
            $body['data'] = (array)json_decode($response['body']);
            $headers = $response['headers'];
            if (is_array($headers)) {
                $limit = array();
                $limit['rateLimitLimit'] = $headers['X-Rate-Limit-Limit'];
                $limit['rateLimitRemaining'] = $headers['X-Rate-Limit-Remaining'];
                $limit['rateLimitReset'] = $headers['X-Rate-Limit-Reset'];
                $body['limit'] = (object)$limit;
                return (object)$body;
            }
            return $body;
        } else {
            throw new APIRequestException($response);
        }
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