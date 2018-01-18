<?php

namespace Swallow\Toolkit\Net\NeteaseIm;

use Monolog\Handler\StreamHandler;
use Swallow\Core\Conf;
use Whoops\Exception\ErrorException;
use Monolog\Logger;

class MsgService extends Service
{
    protected $opeArr = ['users'=> 0, 'chatgroups' => 1];

    /**
     * 消息类型
     * 1 表示图片，
     * 2 表示语音，
     * 3 表示视频，
     * 4 表示地理位置信息，
     * 6 表示文件，
     * 100 自定义消息类型（特别注意，对于未对接易盾反垃圾功能的应用，该类型的消息不会提交反垃圾系统检测）
     * @var array
     */
    protected $msgType = [
        'txt'   => 0,
        'voice' => 1,
        'video' => 3,
        'location' => 4,
        'file'  => 6,
        'custom'    => 100,
    ];
    /**
     * @uri("msg/sendBatchMsg.action")
     *
     * 发送消息
     * @param string      $targetType    接收方类型
     * @param string      $from          发送方
     * @param string|array  $to            接收方
     * @param array       $msg           消息体
     * @param array       $ext           扩展消息
     *
     * @author zhangzeqiang<zhangzeqiang@eelly.net>
     * @since  2017年12月22日
     */
    public function sendMsg($targetType, $from, $to, array $msg, array $ext = [])
    {
        $ope = $this->opeArr[$targetType];
        $bodyContent = [
            'msg' => $msg['msg'],
            'type' => !empty($ext['ext_type']) ? $ext['ext_type'] : '',
            'bodies' => !empty($msg['bodies']) ? $msg['bodies'] : [],
        ];
        $to = is_array($to) ? $to : [$to];
        $msgType = !empty($ext['ext_type']) ? $this->msgType['custom'] : $this->msgType['txt'];
        $body = [
            'ope'       => $ope,
            'fromAccid' => $from,
            'toAccids'  => json_encode($to),
            'type'      => $msgType,//文本消息类型
            'body'      => json_encode($bodyContent),
            'ext'       => json_encode(array_merge(['weichat' => ['originType' => 'php']], $ext)),// 和webim保持一致添加ext.weichat.originType
        ];

        $this->log(json_encode($body));
        $result = $this->getResponse($body);
        return $result;
    }

    /**
     * @uri("msg/sendMsg.action")
     *
     * 单聊发送消息
     *
     * @param string      $targetType    接收方类型
     * @param string      $from          发送方
     * @param string      $to            接收方
     * @param array       $msg           消息体
     * @param array       $ext           扩展消息
     *
     * @author zhangzeqiang<zhangzeqiang@eelly.net>
     * @since  2017年12月23日
     */
    public function sendSingleMsg($targetType, $from, $to, array $msg, array $ext=[])
    {
        $ope = $this->opeArr[$targetType];
        $bodyContent = [
            'msg' => $msg['msg'],
        ];
        $msgType = !empty($ext['ext_type']) ? $this->msgType['custom'] : $this->msgType['txt'];
        $body = [
            'ope'   => $ope,
            'from'  => $from,
            'to'    => $to,
            'type'  => $msgType,//文本消息类型
            'body'   => json_encode($bodyContent),
            'ext'   => json_encode(array_merge(['weichat' => ['originType' => 'php']], $ext)),// 和webim保持一致添加ext.weichat.originType
        ];

        $this->log(json_encode($body));
        $result = $this->getResponse($body);
        return $result;
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

    /**
     * 单聊云端历史消息查询
     * @param int $from                         发送者
     * @param int $to                           接受者
     * @param array $option
     * @param string $option['begintime']       开始时间,单位：毫秒
     * @param string $option['endtime']         截止时间,单位：毫秒
     * @param string $option['limit']           本次查询的消息条数上限(最多100条)
     * @param string $option['reverse']         1按时间正序排列，2按时间降序排列
     *
     * @uri("history/querySessionMsg.action")
     * @author zhangzeqiang<zhangzeqiang@eelly.net>
     * @since  2017年12月22日
     */
    public function querySessionMsg($from, $to, $option = [])
    {
        $queryArr = [
            'from'      => $from,
            'to'        => $to,
            'begintime' => $option['begintime'],
            'endtime'   => $option['endtime'],
            'limit'     => isset($option['limit']) && $option['limit']<=100  ? $option['limit'] : 100,
            'reverse'   => isset($option['reverse']) && in_array($option['reverse'], [1,2]) ? $option['reverse'] : 2
        ];

        $result = $this->getResponse($queryArr);
        return $result;
    }

}