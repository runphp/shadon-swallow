<?php

declare(strict_types=1);

/*
 *
 * (c) eelly.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Swallow\Toolkit\Net\TencentLive\Service;

/**
 * 腾讯云直播码模式查询类接口
 *
 * @author wangjiang<wangjiang@eelly.net>
 */
class QueryService extends Service
{
    /**
     * 查询某条流是否处于正在直播的状态.
     *
     * @param string $streamId 直播码
     * @return mixed
     * @interface("Live_Channel_GetStatus")
     * @see https://cloud.tencent.com/document/api/267/5958
     */
    public function liveChannelGetStatus(string $streamId)
    {
        $args = [
            'Param.s.channel_id' => $streamId
        ];
        $result = $this->getResponse($args);

        return $result;
    }

    /**
     * 用于查询某条直播流某段时间内生成的录制文件
     *
     * @param string $channelId 直播码 有些早期提供的 API 中直播码参数被定义为 channel_id，新的 API 则称直播码为 stream_id，仅历史原因而已。
     * @param int $pageNo 分页页码 从 1 开始，默认为 1
     * @param int $pageSize 分页大小 1~100，默认为 10
     * @param string $sortType 排序方式 asc 表示升序，desc 表示降序，默认 asc
     * @param string $startTime 查询开始时间 格式为：2016-12-10 00:00:00
     * @param string $endTime 查询结束时间 格式为：2016-12-10 00:00:00。结束时间距开始时间一天以内，且不能跨天
     * @return mixed
     * @interface("Live_Tape_GetFilelist")
     * @see https://cloud.tencent.com/document/api/267/5960
     */
    public function liveTapeGetFilelist(
        string $channelId,
        int $pageNo = 1,
        int $pageSize = 10,
        string $sortType = 'asc',
        string $startTime = '',
        string $endTime = ''
    ) {
        $args = [
            'Param.s.channel_id' => $channelId,
            'Param.n.page_no' => $pageNo,
            'Param.n.page_size' => $pageSize,
            'Param.s.sort_type' => $sortType,
        ];
        !empty($startTime) && $args['Param.s.start_time'] = $startTime;
        !empty($endTime) && $args['Param.s.end_time'] = $endTime;
        $result = $this->getResponse($args);

        return $result;
    }

    /**
     * 查询频道列表
     * 可以查询特定状态的频道列表，如可过滤当前处于开启状态的频道
     *
     * @param int $status 0：表示断流，1：表示开启，3：表示关闭  默认是不过滤
     * @param int $pageNo 分页页码 从 1 开始，默认为 1
     * @param int $pageSize 分页大小 10~100，默认为 10
     * @param string $orderField 排序字段 可选字段：create_time，默认为create_time
     * @param int $orderType 排序方法 0：表示正序，1：表示倒序
     * @return mixed
     * @interface("Live_Channel_GetChannelList")
     * @see https://cloud.tencent.com/document/api/267/7997
     */
    public function liveChannelGetChannelList(
        int $status = -1,
        int $pageNo = 1,
        int $pageSize = 10,
        string $orderField = 'create_time',
        int $orderType = 1
    ) {
        $args = [
            'Param.n.page_no' => $pageNo,
            'Param.n.page_size' => $pageSize,
            'Param.s.order_field' => $orderField,
        ];
        in_array($status, [0, 1, 3], true) && $args['Param.n.status'] = $status;
        in_array($orderType, [0, 1], true) && $args['Param.n.order_type'] = $orderType;
        $result = $this->getResponse($args);

        return $result;
    }

    /**
     * 在直播码模式下，用于查询直播中频道列表
     *
     * @return mixed
     * @interface("Live_Channel_GetLiveChannelList")
     * @see https://cloud.tencent.com/document/api/267/8862
     */
    public function liveChannelGetLiveChannelList()
    {
        $args = [];
        $result = $this->getResponse($args);

        return $result;
    }
}