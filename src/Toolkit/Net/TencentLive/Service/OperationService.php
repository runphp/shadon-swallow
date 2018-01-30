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

namespace Swallow\Toolkit\Net\TencentLive\Service;

/**
 * 腾讯云直播码模式操作类接口
 *
 * @author wangjiang<wangjiang@eelly.net>
 */
class OperationService extends Service
{
    /**
     * 对一条直播流执行禁用、断流和允许推流操作
     * 禁用 表示不能再继续使用该流 ID 推流；如果正在推流，则推流会被中断，中断后不可再次推流
     * 一条直播流一旦被设置为【禁用】状态，推流链路将被腾讯云主动断开，并且后续的推流请求也会被拒绝，一条流最长禁用 3 个月，超过 3 个月，禁用失效
     * 断流 表示中断正在推的流，断流后可以再次推流
     * 允许推流 表示启用该流 ID，允许用该流 ID 推流
     *
     * @param string $channelId 直播码
     * @param int $status 开关状态 0 表示禁用，1 表示允许推流，2 表示断流
     * @return mixed
     * @interface("Live_Channel_SetStatus")
     * @see https://cloud.tencent.com/document/api/267/5959
     */
    public function liveChannelSetStatus(string $channelId, int $status)
    {
        $args = [
            'Param.s.channel_id' => $channelId,
            'Param.n.status' => $status,
        ];
        $result = $this->getResponse($args);

        return $result;
    }

    /**
     * 云端混流
     * 将几路输入流云端混成一路流输出
     *
     * @param array $args 参数见文档
     * @return mixed
     * @interface("Mix_StreamV2","POST")
     * @see https://cloud.tencent.com/document/api/267/8832
     */
    public function mixStreamV2(array $args)
    {
        $result = $this->getResponse($args);

        return $result;
    }

    /**
     * 暂停推流并延迟恢复接口
     * 调用该接口可以暂停推某路流（即禁止推流），如果要恢复主播推流，可再次调用该接口或者设置一个恢复时间，到达指定时间后允许推流
     * 最长禁止推流 3 个月，即禁止推流截止时间最长为当前时间往后 3 个月，如果超过 3 个月，则以 3 个月为准
     *
     * @param string $channelId 直播码
     * @param int $abstimeEnd 禁播截止的时间戳  禁播截止的绝对时间，请填写UNIX 时间戳（十进制），系统最多禁播三个月。
     * @param string $action 动作 断流：forbid；恢复推流：resume
     * @return mixed
     * @interface("channel_manager")
     * @see https://cloud.tencent.com/document/api/267/9500
     */
    public function channelManager(string $channelId, int $abstimeEnd, string $action)
    {
        $args = [
            'Param.s.channel_id' => $channelId,
            'Param.n.abstime_end' => $abstimeEnd,
            'Param.s.action' => $action,
        ];
        $result = $this->getResponse($args);

        return $result;
    }

    /**
     * 创建录制任务
     *
     * @param string $channelId 频道 ID
     * @param string $startTime 任务开始时间 标准的 date_time，需要 urlencode，如 2017-01-01%2010:10:01
     * @param string $endTime 任务结束时间 标准的 date_time，需要 urlencode，如 2017-01-01%2010:10:01
     * @param int $taskSubType 默认 0  1 表示开启实时视频录制
     * （1）若开启实时视频录制，调用接口则同步开始录制，此时如果传入任务开始时间参数，任务开始时间参数无效
     * （2）开启实时视频录制的同时如果传入了任务结束时间，则按照任务结束时间结束录制。若没传入，则 30 分钟后自动结束录制
     * （3）实时录制任务开始时间与任务结束时间超过 30 分钟，则 30 分钟后会自动结束录制，实时视频录制建议控制台在 5 分钟以内
     * @param string $fileFormat 录制文件格式 默认 flv；可取值 flv、hls、mp4、aac
     * @param string $recordType 录制文件类型 默认 video
     * 当 record_type 取值“video”时，file_format 可以取值 “flv”,"hls", "mp4"
     * 当 record_type 取值“audio”时，file_format 可以取值 “aac”，“flv”，“hls”，“mp4
     * @return mixed
     * @interface("Live_Tape_Start")
     * @see https://cloud.tencent.com/document/api/267/9567
     */
    public function liveTapeStart(
        string $channelId,
        string $startTime,
        string $endTime,
        int $taskSubType = 0,
        string $fileFormat = 'flv',
        string $recordType = 'video'
    ) {
        $args = [
            'Param.s.channel_id' => $channelId,
            'Param.s.start_time' => $startTime,
            'Param.s.end_time' => $endTime,
            'Param.n.task_sub_type' => $taskSubType,
            'Param.s.file_format' => $fileFormat,
            'Param.s.record_type' => $recordType,
        ];
        $result = $this->getResponse($args);

        return $result;
    }

    /**
     * 结束录制任务
     *
     * @param string $channelId 频道 ID
     * @param string $taskId 任务 ID
     * @param int $taskSubType 是否开启实时视频录制 默认 0，1 表示开启
     * @return mixed
     * @interface("Live_Tape_Stop")
     * @see https://cloud.tencent.com/document/api/267/9568
     */
    public function liveTapeStop(string $channelId, string $taskId, int $taskSubType = 0)
    {
        $args = [
            'Param.s.channel_id' => $channelId,
            'Param.s.task_id' => $taskId,
            'Param.n.task_sub_type' => $taskSubType,
        ];
        $result = $this->getResponse($args);

        return $result;
    }
}