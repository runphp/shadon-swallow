<?php
/*
 * PHP version 5.5
 *
 * @copyright Copyright (c) 2012-2017 EELLY Inc. (http://www.eelly.com)
 * @link      http://www.eelly.com
 * @license   衣联网版权所有
 */

// 第三方服务的一些方法

if (!function_exists('jpushPayloadSend')) {
    /**
     * 极光推送给单个用户.
     *
     *
     * $type参考wiki链接编号1-5
     *
     * @see http://172.18.107.222/pages/viewpage.action?pageId=9306123
     *
     * @param int    $uid     接收方
     * @param string $type    自定义消息类型，方便app做不同处理
     * @param string $title   标题
     * @param string $message 正文
     * @param strint $appName factory 或 store
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since  2017年3月1日
     */
    function jpushPayloadSend($uid, $type, $title = '', $message = '', $appName = 'factory')
    {
        $pushPayload = \Swallow\ThirdParty\JPush\JPushFactory::createPushPayload($appName)->setPlatform([
            'ios',
            'android',
        ])
            ->addAlias((string) $uid)
            ->setNotificationAlert($title)
            ->iosNotification($message, [
            'sound' => 'default',
            'badge' => '+1',
            'extras' => [
                'type' => $type,
            ],
        ])
            ->androidNotification($message, [
            'title' => $title,
            'builder_id' => 1,
            'extras' => [
                'type' => $type,
            ],
        ])
            ->message($message, [
            'title' => $title,
            'content_type' => 'text',
            'extras' => [
                'type' => $type,
            ],
        ]);
        try {
            $pushPayload->send();
        } catch (\JPush\Exceptions\APIConnectionException $e) {
            \Swallow\Core\Log::logWhoopException($e);
        } catch (\JPush\Exceptions\APIRequestException $e) {
            // 出现 cannot find user by this audience，暂时忽略
        }
    }
}

if (!function_exists('isValidObjectId')) {
    /**
     * Check if a value is a valid ObjectId.
     *
     *
     * @param mixed $value
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since 2017年3月17日
     */
    function isValidObjectId($value)
    {
        if ($value instanceof \MongoDB\BSON\ObjectID
            || preg_match('/^[a-f\d]{24}$/i', $value)) {
            $isValid = true;
        } else {
            $isValid = false;
        }

        return $isValid;
    }
}

if (!function_exists('createObjectId')) {
    /**
     * 创建mongo的objectId.
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since  2017年3月20日
     *
     * @param null|mixed $id
     */
    function createObjectId($id = null)
    {
        $mongoId = new \MongoDB\BSON\ObjectID($id);

        return $mongoId;
    }
}
