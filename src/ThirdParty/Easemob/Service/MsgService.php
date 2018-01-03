<?php
/*
 * PHP version 5.5
 *
 * @copyright Copyright (c) 2012-2017 EELLY Inc. (http://www.eelly.com)
 * @link      http://www.eelly.com
 * @license   衣联网版权所有
 */

namespace Swallow\ThirdParty\Easemob\Service;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Swallow\Core\Log;
use Swallow\Exception\StatusCode;
use Swallow\ThirdParty\Easemob\Exception as EasemobException;
use Swallow\ThirdParty\Easemob\Manager;

/**
 * 环信消息体系集成.
 *
 * @see http://docs.easemob.com/start/100serverintegration/50messages
 *
 * @author hehui<hehui@eelly.net>
 *
 * @since 2016年10月1日
 *
 * @version 1.0
 */
class MsgService extends AbstractService
{
    /**
     * 给用户发消息.
     *
     * @var string
     */
    const TARGET_USERS = 'users';

    /**
     * 给群发消息.
     *
     * @var string
     */
    const TARGET_CHATGROUPS = 'chatgroups';

    /**
     * 给聊天室发消息.
     *
     * @var string
     */
    const TARGET_CHATROOMS = 'chatrooms';

    /**
     * 发送文本消息.
     *
     *
     * @param string       $from       发送方
     * @param string|array $to         接收方
     * @param string       $msg        文本消息
     * @param string       $targetType 接收方类型
     *
     * @return mixed
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since 2016年10月1日
     */
    public function sendText($from, $to, $msg, $targetType = self::TARGET_USERS)
    {
        $msg = [
            'type' => 'txt',
            'msg' => $msg,
        ];
        $result = $this->sendMsg($targetType, $from, $to, $msg);

        return $result;
    }

    /**
     * 发送图片消息.
     *
     *
     * @param string       $from       发送方
     * @param string|array $to         接收方
     * @param string       $filePath   图片路径
     * @param string       $targetType 接收方类型
     *
     * @return mixed
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since  2016年10月1日
     */
    public function sendImage($from, $to, $filePath, $targetType = self::TARGET_USERS)
    {
        $chatFilesService = Manager::chatFilesService();
        $fileArr = $chatFilesService->uploadFile($filePath);
        $msg = [
            'type' => 'img',
            'url' => $fileArr['uri'].'/'.$fileArr['entities'][0]['uuid'],
            'filename' => $filePath,
            'secret' => $fileArr['entities'][0]['share-secret'],
            // size 暂时没有发现什么用处
            /* 'size' => [
                'width' => 200,
                'height' => 200
            ] */
        ];
        $result = $this->sendMsg($targetType, $from, $to, $msg);

        return $result;
    }

    /**
     * 发送扩展消息.
     *
     *
     * @param string       $from       发送方
     * @param string|array $to         接收方
     * @param array        $body       扩展消息体
     * @param string       $msg        消息内容
     * @param string       $targetType 接收方类型
     * @param string       $extType
     *
     * @return mixed|string
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since 2016年10月10日
     */
    public function sendExtMsg($from, $to, $extType, array $body, $msg = '', $targetType = self::TARGET_USERS)
    {
        $msg = [
            'type' => 'txt',
            'msg' => $msg,
        ];
        $ext = [
            'ext_type' => $extType,
            'bodies' => [$body],
        ];
        $result = $this->sendMsg($targetType, $from, $to, $msg, $ext);

        return $result;
    }

    /**
     * 发送消息.
     *
     * @see http://docs.easemob.com/start/100serverintegration/50messages
     *
     * @param string       $targetType 接收方类型
     * @param string       $from       发送方
     * @param string|array $to         接收方
     * @param array        $msg        消息体
     * @param array        $ext        扩展消息
     *
     * @return mixed
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since 2016年10月1日
     */
    public function sendMsg($targetType, $from, $to, array $msg, array $ext = [])
    {
        $body = [
            'target_type' => $targetType,
            'from' => $from,
            'target' => (array) $to,
            'msg' => $msg,
        ];
        // 和webim保持一直添加ext.weichat.originType
        $body['ext'] = array_merge(['weichat' => ['originType' => 'php']], $ext);
        $this->log(json_encode($body));
        $result = $this->getManager()->request('message', self::POST, $body);

        return $result;
    }

    /**
     * 根据时间条件下载历史消息文件.
     *
     * > 查询的时间格式为10位数字形式(YYYYMMDDHH),例如要查询2016年12月10号7点到8点的历史记录，则需要输入2016121007,7:00:00的信息也会包含在这个文件里
     *
     * @param string $time
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since  2017年3月7日
     */
    public function chatMessages($time)
    {
        $service = "chatmessages/$time";
        $result = $this->getManager()->request($service, self::GET);

        return $result;
    }

    /**
     * 消息实时回调请求
     *
     *
     * @param string $url  回调地址
     * @param array  $body 消息体
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since  2017年3月21日
     */
    public function msgCallbackRequest($url, array $body)
    {
        $serviceClient = $this->getManager()->getServiceClient();
        $securityOptions = $this->getManager()->getSecurityOptions();
        $body['security'] = md5($body['callId'].$securityOptions['request'].$body['timestamp']);
        $options = [
            'body' => json_encode($body),
        ];

        return $serviceClient->post($url, $options);
    }

    /**
     * 消息实时回调响应.
     *
     * @param array    $callMsg  回调消息
     * @param callable $callBack
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since  2017年3月21日
     */
    public function msgCallbackResponse($callMsg, callable $callBack)
    {
        if (!is_array($callMsg) || !isset($callMsg['callId'], $callMsg['timestamp'], $callMsg['security'])) {
            throw new EasemobException('请求参数错误', StatusCode::BAD_REQUEST);
        }
        $securityOptions = $this->getManager()->getSecurityOptions();
        // 回调消息校验
        // 签名。格式如下: MD5（callId+约定的key+timestamp）
        if (md5($callMsg['callId'].$securityOptions['request'].$callMsg['timestamp']) != $callMsg['security']) {
            throw new EasemobException('回调的正文签名错误', StatusCode::REQUEST_FORBIDDEN);
        }
        $return = [
            'callId' => $callMsg['callId'], // 与环信推送的一致
            'accept' => true,               //表明接受了此推送
            'reason' => '', // 可选，accept为false时使用
            'security' => md5($callMsg['callId'].$securityOptions['response'].'true'), //签名。格式如下: MD5（callId+约定的key+"true"）
        ];
        try {
            $callBack($callMsg);
        } catch (\Exception $e) {
            Log::logWhoopException($e);
            $return['accept'] = false;
            $return['reason'] = $e->getMessage();
        }

        return $return;
    }

    /**
     * @param string $message
     *
     * @return \Monolog\Boolean
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since  2017年3月7日
     */
    private function log($message)
    {
        static $logger;
        if (null === $logger) {
            $logger = new Logger('easemob');
            $logger->pushHandler(new StreamHandler(LOG_PATH.'/easemob.'.date('Ymd').'.txt', Logger::INFO));
        }

        return $logger->info($message);
    }
}
