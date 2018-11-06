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

class BaseService extends Service
{
    protected $ttl = 86400;

    protected $pushUrl = 'rtmp://push.eelly.com/live/%s?%s';

    protected $bizId = '3344';

    protected $pushKey = '4bda0c7a4fca42abdc7767f5f4d2d4f2';

    protected $playUrl = [
        'RTMP' => 'rtmp://play.eelly.com/live/%s',
        'FLV' => 'http://play.eelly.com/live/%s.flv',
        'HLS' => 'http://play.eelly.com/live/%s.m3u8',
    ];

    protected function init()
    {
        !empty($this->liveConfig['bizId']) && $this->bizId = $this->liveConfig['bizId'];
        !empty($this->liveConfig['pushKey']) && $this->pushKey = $this->liveConfig['pushKey'];
    }

    /**
     * 获取腾讯云直播推流地址
     *
     * @param string $liveId 用来区别不同推流地址的唯一id
     * @return string
     */
    public function getPushUrl(string $liveId)
    {
        $txTime = strtoupper(base_convert(time() + $this->ttl, 10, 16));
        // livecode = bizid+"_"+env+"_"+stream_id 如 8888_test_123456
        // 直播码
        $livecode = sprintf('%s_%s_%s',
                $this->bizId,
                getenv('APPLICATION_ENV') ?: 'prod',
                $liveId
            );
        // txSecret = MD5( KEY + livecode + txTime )
        $txSecret = md5($this->pushKey . $livecode . $txTime);
        $extStr = http_build_query([
            "bizid" => $this->bizId,
            "txSecret" => $txSecret,
            "txTime" => $txTime
        ]);
        $pushUrl = sprintf($this->pushUrl,
                $livecode,
                $extStr
            );

        return $pushUrl;
    }

    /**
     * 获取腾讯云直播播放地址
     *
     * @param string $liveId 用来区别不同推流地址的唯一id
     * @return array
     */
    public function getPlayUrl(string $liveId)
    {
        $playUrl = [];
        $liveCode = sprintf('%s_%s_%s',
                $this->bizId,
                getenv('APPLICATION_ENV') ?: 'prod',
                $liveId
            );
        foreach ($this->playUrl as $type => $url){
            $playUrl[$type] = sprintf($url,
                $liveCode
            );
        }

        return $playUrl;
    }

}
