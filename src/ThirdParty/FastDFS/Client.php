<?php
/*
 * PHP version 5.5
 *
 * @copyright Copyright (c) 2012-2016 EELLY Inc. (http://www.eelly.com)
 * @link      http://www.eelly.com
 * @license   衣联网版权所有
 */
namespace Swallow\ThirdParty\FastDFS;

use Swallow\Core\Conf;

/**
 * FastDFS 客户端
 *
 * 使用示例:
 *
 * ```
 * // 上传文件
 * $fileName = Client::uploadFile($filePath);
 *
 * // 删除文件
 * Client::deleteFile($filename)
 * ```
 *
 * @author    hehui<hehui@eelly.net>
 * @since     2016年10月5日
 * @version   1.0
 */
class Client
{

    /**
     *
     * @var Tracker
     */
    private $tracker;

    /**
     *
     * @var array
     */
    private $storageInfo;

    /**
     *
     * @var Storage
     */
    private $storage;

    public static function getInstance()
    {
        static $self;
        if (null !== $self) {
            return $self;
        }
        $config = Conf::get('fastdfs');
        return $self = new self($config);
    }

    public function __construct(array $config)
    {
        $index = time() % count($config['group']);
        $this->tracker = new Tracker($config['host'], $config['port']);
        $this->storageInfo = $this->tracker->applyStorage($config['group'][$index]);
        $this->storage = new Storage($this->storageInfo['storage_addr'], $this->storageInfo['storage_port']);
    }

    public function getStorageInfo()
    {
        return $this->storageInfo;
    }

    public function getStorage()
    {
        return $this->storage;
    }

    public function setStorage($storage)
    {
        $this->storage = $storage;
    }

    /**
     * 上传文件
     *
     *
     * @param stirng $filename 文件路径
     * @param string $ext 扩展名
     * @return string 文件路径
     * @author hehui<hehui@eelly.net>
     * @since 2016年10月5日
     */
    public static function uploadFile($filename, $ext = '')
    {
        $client = self::getInstance();
        static $prevFilename;
        if ($prevFilename == $filename) {
            // 如果重试进行重连
            $storageInfo = $client->getStorageInfo();
            $client->setStorage(new Storage($storageInfo['storage_addr'], $storageInfo['storage_port']));
        }
        $prevFilename = $filename;
        $result = $client->getStorage()->uploadFile($client->getStorageInfo()['storage_index'], $filename, $ext);
        return $result['group'] . '/' . $result['path'];
    }

    /**
     * 删除文件
     *
     *
     * @param string $filename 文件路径
     * @return boolean
     * @author hehui<hehui@eelly.net>
     * @since  2016年10月5日
     */
    public static function deleteFile($filename)
    {
        list($groupName, $filePath) = explode('/', $filename, 2);
        $client = self::getInstance();
        $result = $client->getStorage()->deleteFile($groupName, $filePath);
        return $result;
    }
}
