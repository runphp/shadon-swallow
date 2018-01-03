<?php
/*
 * PHP version 5.5
 *
 * @copyright Copyright (c) 2012-2017 EELLY Inc. (http://www.eelly.com)
 * @link      http://www.eelly.com
 * @license   衣联网版权所有
 */

namespace Swallow\ThirdParty\Easemob\Service;

use Swallow\ThirdParty\FastDFS\Client;

/**
 * 聊天文件相关.
 *
 * @author    hehui<hehui@eelly.net>
 *
 * @since     2016年10月1日
 *
 * @version   1.0
 */
class ChatFilesService extends AbstractService
{
    /**
     * 上传语音/图片文件.
     *
     * @param string $filePath 文件路径
     *
     * @return mixed
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since  2016年10月1日
     */
    public function uploadFile($filePath)
    {
        $header = [
            'restrict-access' => true,
        ];
        $multipart = [
            [
                'name' => 'file',
                'contents' => file_get_contents($filePath),
            ],
        ];

        return $this->getManager()->request('chatfiles', self::POST, [], $header, $multipart, false /*这里发现官方不需要认证哈*/);
    }

    /**
     * 下载文件.
     *
     *
     * @param string $uuid        uuid
     * @param string $shareSecret share-secret
     * @param string $fileExt     文件后缀
     *
     * @return string
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since  2016年10月5日
     */
    public function downloadFile($uuid, $shareSecret, $fileExt = '')
    {
        $service = 'chatfiles/'.$uuid;
        $header = [
            'share-secret' => $shareSecret,
            'Accept' => 'application/octet-stream',
        ];

        return $this->getManager()->request($service, self::GET, [], $header, [], true, Client::class, $fileExt);
    }
}
