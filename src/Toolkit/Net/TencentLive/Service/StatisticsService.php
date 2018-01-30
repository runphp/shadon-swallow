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
 * 腾讯云直播码模式统计类接口
 *
 * @author wangjiang<wangjiang@eelly.net>
 */
class StatisticsService extends Service
{
    protected $baseUrl = 'http://statcgi.video.qcloud.com/common_access';

    protected function init()
    {
        $this->setBaseUrl($this->baseUrl);
    }

    /**
     * 查询指定直播流的推流和播放信息
     *
     * @param int $pageNo 分页页码  从 1 开始，默认为 1
     * @param int $pageSize 分页大小  1~300，默认为 300
     * @param string $streamId 直播码 如不设置 stream_id：查询所有正在直播中的流
     * @param string $pullDomain 即播放域名，如果不填则返回所有域名的播放数据
     * @return mixed
     * @interface("Get_LiveStat")
     * @see https://cloud.tencent.com/document/api/267/6110
     */
    public function getLiveStat(
        int $pageNo = 1,
        int $pageSize = 300,
        string $streamId = '',
        string $pullDomain = ''
        ) {
            $args = $this->getLiveStatCommonArgs($pageNo, $pageSize, $streamId, $pullDomain);
            $result = $this->getResponse($args);

            return $result;
    }

    /**
     * 仅返回推流统计信息以提高查询效率
     *
     * @param int $pageNo 分页页码  从 1 开始，默认为 1
     * @param int $pageSize 分页大小 1~300，默认为 300
     * @param string $streamId 直播码 如不设置 stream_id：查询所有正在直播中的流
     * @param string $pullDomain 即播放域名，如果不填则返回所有域名的播放数据
     * @return mixed
     * @interface("Get_LivePushStat")
     * @see https://cloud.tencent.com/document/api/267/6110
     */
    public function getLivePushStat(
        int $pageNo = 1,
        int $pageSize = 300,
        string $streamId = '',
        string $pullDomain = ''
        ) {
            $args = $this->getLiveStatCommonArgs($pageNo, $pageSize, $streamId, $pullDomain);
            $result = $this->getResponse($args);

            return $result;
    }

    /**
     * 仅返回播放统计信息以提高查询效率
     *
     * @param int $pageNo 分页页码 从 1 开始，默认为 1
     * @param int $pageSize 分页大小 1~300，默认为 300
     * @param string $streamId 直播码 如不设置 stream_id：查询所有正在直播中的流
     * @param string $pullDomain 即播放域名，如果不填则返回所有域名的播放数据
     * @return mixed
     * @interface("Get_LivePlayStat")
     * @see https://cloud.tencent.com/document/api/267/6110
     */
    public function getLivePlayStat(
        int $pageNo = 1,
        int $pageSize = 300,
        string $streamId = '',
        string $pullDomain = ''
        ) {
            $args = $this->getLiveStatCommonArgs($pageNo, $pageSize, $streamId, $pullDomain);
            $result = $this->getResponse($args);

            return $result;
    }

    /**
     * 获取统计接口公共请求参数
     *
     * @return array
     */
    private function getLiveStatCommonArgs(...$args)
    {
        $interfaceArgs = [
            'Param.n.page_no' => $args[0] ?: 1,
            'Param.n.page_size' => $args[1] ?: 300,
        ];
        !empty($args[2]) && $interfaceArgs['Param.s.stream_id'] = $args[2];
        !empty($args[3]) && $interfaceArgs['Param.s.pull_domain'] = $args[3];

        return $interfaceArgs;
    }

    /**
     * 获取推流历史信息
     * 可获取指定时间段内推流信息 推流信息的统计数据每 5 秒钟更新一次
     * 使用该接口需要后台配置，如需调用该接口，请联系腾讯商务人员或者 提交工单，联系电话：4009-100-100。
     *
     * @param string $streamId 直播码
     * @param int $startTime 查询起始时间 3 天内的数据时间戳
     * @param int $endTime 查询终止时间 建议查询跨度不大于 2 小时时间戳
     * @return mixed
     * @interface("Get_LivePushStatHistory")
     * @see https://cloud.tencent.com/document/api/267/9579
     */
    public function getLivePushStatHistory(string $streamId, int $startTime, int $endTime)
    {
        $args = [
            'Param.s.stream_id' => $streamId,
            'Param.n.start_time' => $startTime,
            'Param.n.end_time' => $endTime,
        ];
        $result = $this->getResponse($args);

        return $result;
    }

    /**
     * 获取播放统计历史信息
     * 可获取指定时间段内播放信息 播放信息的统计数据每 1 分钟更新一次
     * 使用该接口需要后台配置，如需调用该接口，请联系腾讯商务人员或者 提交工单，联系电话：4009-100-100。
     *
     * @param int $startTime
     * @param int $endTime
     * @param string $streamId
     * @param string $domain
     * @return mixed
     * @interface("Get_LivePlayStatHistory")
     * @see https://cloud.tencent.com/document/api/267/9580
     */
    public function getLivePlayStatHistory(int $startTime, int $endTime, string $streamId = '', string $domain = '')
    {
        $args = [
            'Param.n.start_time' => $startTime,
            'Param.n.end_time' => $endTime,
        ];
        !empty($streamId) && $args['Param.s.stream_id'] = $streamId;
        !empty($domain) && $args['Param.s.domain'] = $domain;
        $result = $this->getResponse($args);

        return $result;
    }
}