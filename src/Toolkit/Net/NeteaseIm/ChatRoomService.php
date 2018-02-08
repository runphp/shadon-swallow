<?php

declare(strict_types=1);

/*
 * This file is part of eelly package.
 *
 * (c) eelly.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swallow\Toolkit\Net\NeteaseIm;

use Whoops\Exception\ErrorException;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * 网易云信聊天室
 */
class ChatRoomService extends Service
{
    /**
     * 创建聊天室
     *
     * @param string $creator 聊天室属主的账号accid
     * @param string $name 聊天室名称，长度限制128个字符
     * @param string $announcement 公告，长度限制4096个字符
     * @param string $broadcasturl 直播地址，长度限制1024个字符
     * @param string $ext 扩展字段，最长4096字符
     * @param int $queuelevel 队列管理权限：0:所有人都有权限变更队列，1:只有主播管理员才能操作变更。默认0
     * @return mixed
     * @uri("chatroom/create.action")
     */
    public function createChatRoom(
        string $creator,
        string $name,
        string $announcement = '',
        string $broadcasturl = '',
        string $ext = '',
        int $queuelevel = 0
    ) {
        $args = [
            'creator' => $creator,
            'name' => $name,
        ];
        !empty($announcement) && $args['announcement'] = $announcement;
        !empty($broadcasturl) && $args['broadcasturl'] = $broadcasturl;
        !empty($ext) && $args['ext'] = $ext;
        in_array($queuelevel, [0, 1], true) && $args['queuelevel'] = $queuelevel;

        return $this->getResponse($args);
    }

    /**
     * 查询聊天室信息
     *
     * @param int $roomid 聊天室id
     * @param string $needOnlineUserCount 是否需要返回在线人数，true或false，默认false
     * @return mixed
     * @uri("chatroom/get.action")
     */
    public function getInfo(int $roomid, string $needOnlineUserCount = 'false')
    {
        $args = [
            'roomid' => $roomid,
            'needOnlineUserCount' => $needOnlineUserCount,
        ];

        return $this->getResponse($args);
    }

    /**
     * 批量查询聊天室信息
     *
     * @param string $roomids 多个roomid，格式为：["6001","6002","6003"]（JSONArray对应的roomid，如果解析出错，会报414错误），限20个roomid
     * @param string $needOnlineUserCount 是否需要返回在线人数，true或false，默认false
     * @return mixed
     * @uri("chatroom/getBatch.action")
     */
    public function batchGetInfo(string $roomids, string $needOnlineUserCount = 'false')
    {
        $checkRoonids = json_decode($roomids, true);
        $errorMsg = '';
        if (JSON_ERROR_NONE !== json_last_error()){
            $errorMsg = json_last_error_msg();
        }elseif (20 < count($checkRoonids)){
            $errorMsg = '限20个roomid';
        }
        throwIf(!empty($errorMsg), LogicException::class, $errorMsg);

        $args = [
            'roomids' => $roomids,
            'needOnlineUserCount' => $needOnlineUserCount,
        ];

        return $this->getResponse($args);
    }

    /**
     * 更新聊天室信息
     *
     * @param int $roomid 聊天室id
     * @param string $name 聊天室名称，长度限制128个字符
     * @param string $announcement 公告，长度限制4096个字符
     * @param string $broadcasturl 直播地址，长度限制1024个字符
     * @param string $ext 扩展字段，长度限制4096个字符
     * @param string $needNotify true或false,是否需要发送更新通知事件，默认true
     * @param string $notifyExt 通知事件扩展字段，长度限制2048
     * @param int $queuelevel 队列管理权限：0:所有人都有权限变更队列，1:只有主播管理员才能操作变更
     * @return mixed
     * @uri("chatroom/update.action")
     */
    public function updateChatRoom(
        int $roomid,
        string $name = '',
        string $announcement = '',
        string $broadcasturl = '',
        string $ext = '',
        string $needNotify = 'true',
        string $notifyExt = '',
        int $queuelevel = 0
    ) {
        $args = [
            'roomid' => $roomid,
            'needNotify' => $needNotify,
        ];
        !empty($name) && $args['name'] = $name;
        !empty($announcement) && $args['announcement'] = $announcement;
        !empty($broadcasturl) && $args['broadcasturl'] = $broadcasturl;
        !empty($ext) && $args['ext'] = $ext;
        !empty($notifyExt) && $args['notifyExt'] = $notifyExt;
        in_array($queuelevel, [0, 1], true) && $args['queuelevel'] = $queuelevel;

        return $this->getResponse($args);
    }

    /**
     * 修改聊天室开/关闭状态
     *
     * @param int $roomid 聊天室id
     * @param string $operator 操作者账号，必须是创建者才可以操作
     * @param string $valid true或false，false:关闭聊天室；true:打开聊天室
     * @return mixed
     * @uri("chatroom/toggleCloseStat.action")
     */
    public function toggleCloseStat(int $roomid, string $operator, string $valid)
    {
        $args = [
            'roomid' => $roomid,
            'operator' => $operator,
            'valid' => $valid,
        ];

        return $this->getResponse($args);
    }

    /**
     * 设置聊天室内用户角色
     *
     * @param int $roomid 聊天室id
     * @param string $operator 操作者账号accid
     * @param string $target 被操作者账号accid
     * @param int $opt 操作：
     * 1:设置为管理员，operator必须是创建者
     * 2:设置普通等级用户，operator必须是创建者或管理员
     * -1:设为黑名单用户，operator必须是创建者或管理员
     * -2:设为禁言用户，operator必须是创建者或管理员
     *
     * @param string $optvalue true或false，true:设置；false:取消设置
     * @param string $notifyExt 通知扩展字段，长度限制2048，请使用json格式
     * @return mixed
     * @uri("chatroom/setMemberRole.action")
     */
    public function setMemberRole(
        int $roomid,
        string $operator,
        string $target,
        int $opt,
        string $optvalue,
        string $notifyExt = ''
    ) {
        $args = [
            'roomid' => $roomid,
            'operator' => $operator,
            'target' => $target,
            'opt' => $opt,
            'optvalue' => $optvalue,
        ];
        !empty($notifyExt) && $args['notifyExt'] = $notifyExt;

        return $this->getResponse($args);
    }

    /**
     * 请求聊天室地址
     *
     * @param int $roomid 聊天室id
     * @param string $accid 进入聊天室的账号
     * @param int $clienttype 1:weblink（客户端为web端时使用）; 2:commonlink（客户端为非web端时使用）, 默认1
     * @return mixed
     * @uri("chatroom/requestAddr.action")
     */
    public function requestAddr(int $roomid, string $accid, int $clienttype = 1)
    {
        $args = [
            'roomid' => $roomid,
            'accid' => $accid,
            'clienttype' => $clienttype,
        ];

        return $this->getResponse($args);
    }

    /**
     * 发送聊天室消息
     *
     * @param int $roomid 聊天室id
     * @param string $msgId 客户端消息id，使用uuid等随机串，msgId相同的消息会被客户端去重
     * @param string $fromAccid 消息发出者的账号accid
     * @param int $msgType 消息类型：
     * 0: 表示文本消息，
     * 1: 表示图片，
     * 2: 表示语音，
     * 3: 表示视频，
     * 4: 表示地理位置信息，
     * 6: 表示文件，
     * 10: 表示Tips消息，
     * 100: 自定义消息类型（特别注意，对于未对接易盾反垃圾功能的应用，该类型的消息不会提交反垃圾系统检测）
     * @param int $resendFlag 重发消息标记，0：非重发消息，1：重发消息，如重发消息会按照msgid检查去重逻辑
     * @param string $attach 消息内容，格式同消息格式示例中的body字段,长度限制4096字符
     * @param string $ext 消息扩展字段，内容可自定义，请使用JSON格式，长度限制4096字符
     * @param string $antispam 对于对接了易盾反垃圾功能的应用，本消息是否需要指定经由易盾检测的内容（antispamCustom
     * @param string $antispamCustom 在antispam参数为true时生效。
     * @param int $skipHistory 是否跳过存储云端历史，0：不跳过，即存历史消息；1：跳过，即不存云端历史；默认0
     * @param string $bid 反垃圾业务ID，实现“单条消息配置对应反垃圾”，若不填则使用原来的反垃圾配置
     * @param string $highPriority true表示是高优先级消息，云信会优先保障投递这部分消息；false表示低优先级消息。默认false
     * @return mixed
     * @uri("chatroom/sendMsg.action")
     * @see http://dev.netease.im/docs/product/IM即时通讯/服务端API文档/聊天室?#发送聊天室消息
     */
    public function sendMsg(
        int $roomid,
        string $msgId,
        string $fromAccid,
        int $msgType,
        int $resendFlag,
        string $attach = '',
        string $ext = '',
        string $antispam = 'false',
        string $antispamCustom = '',
        int $skipHistory = 0,
        string $bid = '',
        string $highPriority = 'false'
    ) {
        $args = [
            'roomid' => $roomid,
            'msgId' => $msgId,
            'fromAccid' => $fromAccid,
            'msgType' => $msgType,
            'resendFlag' => $resendFlag,
            'attach' => $attach,
            'ext' => $ext,
            'antispam' => $antispam,
            'antispamCustom' => $antispamCustom,
            'skipHistory' => $skipHistory,
            'bid' => $bid,
            'highPriority' => $highPriority,
        ];
        $this->log(json_encode($args));

        return $this->getResponse($args);
    }

    /**
     * 往聊天室内添加机器人
     *
     * @param int $roomid 聊天室id
     * @param string $accids 机器人账号accid列表，必须是有效账号，账号数量上限100个
     * @param string $roleExt 机器人信息扩展字段，请使用json格式，长度4096字符
     * @param string $notifyExt 机器人进入聊天室通知的扩展字段，请使用json格式，长度2048字符
     * @return mixed
     * @uri("chatroom/addRobot.action")
     */
    public function addRobot(int $roomid, string $accids, string $roleExt = '', string $notifyExt = '')
    {
        $checkAccids = json_decode($accids, true);
        $errorMsg = '';
        if (JSON_ERROR_NONE !== json_last_error()){
            $errorMsg = json_last_error_msg();
        }elseif (100 < count($checkAccids)){
            $errorMsg = '账号数量上限100个';
        }
        throwIf(!empty($errorMsg), LogicException::class, $errorMsg);

        $args = [
            'roomid' => $roomid,
            'accids' => $accids,
        ];
        !empty($roleExt) && $args['roleExt'] = $roleExt;
        !empty($notifyExt) && $args['notifyExt'] = $notifyExt;

        return $this->getResponse($args);
    }

    /**
     * 从聊天室内删除机器人
     *
     * @param int $roomid 聊天室id
     * @param string $accids 机器人账号accid列表，必须是有效账号，账号数量上限100个
     * @return mixed
     * @uri("chatroom/removeRobot.action")
     */
    public function removeRobot(int $roomid, string $accids)
    {
        $checkAccids = json_decode($accids, true);
        $errorMsg = '';
        if (JSON_ERROR_NONE !== json_last_error()){
            $errorMsg = json_last_error_msg();
        }elseif (100 < count($checkAccids)){
            $errorMsg = '账号数量上限100个';
        }
        throwIf(!empty($errorMsg), LogicException::class, $errorMsg);

        $args = [
            'roomid' => $roomid,
            'accids' => $accids,
        ];

        return $this->getResponse($args);
    }

    /**
     * 设置临时禁言状态
     *
     * @param int $roomid 聊天室id
     * @param string $operator 操作者accid,必须是管理员或创建者
     * @param string $target 被禁言的目标账号accid
     * @param int $muteDuration 0:解除禁言;>0设置禁言的秒数，不能超过2592000秒(30天)
     * @param string $needNotify 操作完成后是否需要发广播，true或false，默认true
     * @param string $notifyExt 通知广播事件中的扩展字段，长度限制2048字符
     * @return mixed
     * @uri("chatroom/temporaryMute.action")
     */
    public function temporaryMute(
        int $roomid,
        string $operator,
        string $target,
        int $muteDuration,
        string $needNotify = 'true',
        string $notifyExt = ''
    ) {
        throwIf(0 > $muteDuration || 2592000 < $muteDuration, LogicException::class, 'muteDuration的有效范围为0-2592000');

        $args = [
            'roomid' => $roomid,
            'operator' => $operator,
            'target' => $target,
            'muteDuration' => $muteDuration,
            'needNotify' => $needNotify,
            'notifyExt' => $notifyExt,
        ];

        return $this->getResponse($args);
    }

    /**
     * 将聊天室整体禁言
     *
     * @param int $roomid 聊天室id
     * @param string $operator 操作者accid，必须是管理员或创建者
     * @param string $mute true或false
     * @param string $needNotify true或false，默认true
     * @param string $notifyExt 通知扩展字段
     * @return mixed
     * @uri("chatroom/muteRoom.action")
     */
    public function muteRoom(
        int $roomid,
        string $operator,
        string $mute,
        string $needNotify = 'true',
        string $notifyExt = ''
    ) {
        $args = [
            'roomid' => $roomid,
            'operator' => $operator,
            'mute' => $mute,
            'needNotify' => $needNotify,
            'notifyExt' => $notifyExt,
        ];

        return $this->getResponse($args);
    }

    /**
     * 查询聊天室统计指标TopN
     *
     * @param int $topn topn值，可选值 1~500，默认值100
     * @param int $timestamp 需要查询的指标所在的时间坐标点，不提供则默认当前时间，单位秒/毫秒皆可
     * @param string $period 统计周期，可选值包括 hour/day, 默认hour
     * @param string $orderby 取排序值,可选值 active/enter/message,分别表示按日活排序，进入人次排序和消息数排序， 默认active
     * @return mixed
     * @uri("stats/chatroom/topn.action")
     */
    public function topn(int $topn = 100, int $timestamp = 0, string $period = 'hour', string $orderby = 'active')
    {
        $args = [
            'topn' => $topn,
            'timestamp' => $timestamp,
            'period' => $period,
            'orderby' => $orderby,
        ];

        return $this->getResponse($args);
    }

    /**
     * 分页获取成员列表
     *
     * @param int $roomid 聊天室id
     * @param int $type 需要查询的成员类型,0:固定成员(房主、管理员、普通成员和受限成员);1:非固定成员(游客);2:仅返回在线的固定成员
     * @param int $endtime 单位毫秒，按时间倒序最后一个成员的时间戳,0表示系统当前时间
     * @param int $limit 返回条数，<=100
     * @return mixed
     * @uri("chatroom/membersByPage.action")
     */
    public function membersByPage(int $roomid, int $type, int $endtime, int $limit)
    {
        throwIf(1 > $limit|| 100 < $limit, LogicException::class, 'limit的有效范围为1-100');

        $args = [
            'roomid' => $roomid,
            'type' => $type,
            'endtime' => $endtime,
            'limit' => $limit,
        ];

        return $this->getResponse($args);
    }

    /**
     * 批量获取在线成员信息
     *
     * @param int $roomid 聊天室id
     * @param string $accids ["abc","def"], 账号列表，最多200条
     * @return mixed
     * @uri("chatroom/queryMembers.action")
     */
    public function queryMembers(int $roomid, string $accids)
    {
        $checkAccids = json_decode($accids, true);
        $errorMsg = '';
        if (JSON_ERROR_NONE !== json_last_error()){
            $errorMsg = json_last_error_msg();
        }elseif (200 < count($checkAccids)){
            $errorMsg = '账号数量上限100个';
        }
        throwIf(!empty($errorMsg), LogicException::class, $errorMsg);

        $args = [
            'roomid' => $roomid,
            'accids' => $accids,
        ];

        return $this->getResponse($args);
    }

    /**
     * 变更聊天室内的角色信息
     *
     * @param int $roomid 聊天室id
     * @param string $accid 需要变更角色信息的accid
     * @param string $save 变更的信息是否需要持久化，默认false，仅对聊天室固定成员生效
     * @param string $needNotify 是否需要做通知
     * @param string $notifyExt 通知的内容，长度限制2048
     * @param string $nick 聊天室室内的角色信息：昵称
     * @param string $avator 聊天室室内的角色信息：头像
     * @param string $ext 聊天室室内的角色信息：开发者扩展字段
     * @return mixed
     * @uri("chatroom/updateMyRoomRole.action")
     */
    public function updateMyRoomRole(
        int $roomid,
        string $accid,
        string $save = 'false',
        string $needNotify = 'false',
        string $notifyExt = '',
        string $nick = '',
        string $avator = '',
        string $ext = ''
    ) {
        $args = [
            'roomid' => $roomid,
            'accid' => $accid,
            'save' => $save,
            'needNotify' => $needNotify,
        ];
        !empty($notifyExt) && $args['notifyExt'] = $notifyExt;
        !empty($nick) && $args['nick'] = $nick;
        !empty($avator) && $args['avator'] = $avator;
        !empty($ext) && $args['ext'] = $ext;

        return $this->getResponse($args);
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
//         dump($message);
        static $logger;
        if (null === $logger) {
            $logger = new Logger('easemob');
            $logger->pushHandler(new StreamHandler(LOG_PATH.'/chatRoomMsg.'.date('Ymd').'.txt', Logger::INFO));
        }

        return $logger->info($message);
    }
}