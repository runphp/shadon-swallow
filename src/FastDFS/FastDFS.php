<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\FastDFS;

/************************************************
 * PHP-FastDFS-Client (FOR FastDFS v4.0.6)
 ************************************************
 * @Description: 用PHP Socket实现的FastDFS客户端
 *************************************************/

/* 类自动加载有问题暂时添加判断进行处理 */

if (!defined('SWALLOW_FASTDFS_FASTDFS')):

define('SWALLOW_FASTDFS_FASTDFS', 'SWALLOW_FASTDFS_FASTDFS');

// define('FDFS_FILE_ID_SEPERATOR', '/'); 未使用 ?
define('FDFS_PROTO_PKG_LEN_SIZE', 8);
define('FDFS_HEADER_LENGTH', 10);
define('FDFS_IP_ADDRESS_SIZE', 16);
define('FDFS_FILE_EXT_NAME_MAX_LEN', 6);
define('FDFS_GROUP_NAME_MAX_LEN', 16);
define('FDFS_OVERWRITE_METADATA', 1);
// define('FDFS_MERGE_METADATA', 2); 未使用 ?
// 连接超时时间
define('FDFS_CONNECT_TIME_OUT', 5);
define('FDFS_FILE_PREFIX_MAX_LEN', 16);
//传输超时时间
//define('FDFS_TRANSFER_TIME_OUT', 0); 未使用 ?

endif;
/* ./ 类自动加载有问题暂时添加判断进行处理 */


/**
 * FastDFS PECL 兼容实现
 *
 * 实现兼容 FastDFS PECL 兼容的文件上传与下载类接口
 */
class FastDFS
{

    const FDFS_PROTO_PKG_LEN_SIZE = 8;
    /**
     * FastDFS Tracker 对象
     *
     * @access private
     */
    private $tracker;

    /**
     * 连接 Tracker 服务器
     *
     * @access public
     * @param string $ip_addr Tracker IP Address
     * @param integer $port Tracker Port
     * @return fixed
     */
    public function connect_server($ip_addr, $port)
    {
        return $this->tracker = new FastDFSTracker($ip_addr, $port);
    }

    /**
     * 断开连接
     *
     * @access public
     * @param array $server_info 连接服务器的IP与端口号
     * @return booleam
     */
    public function disconnect_server($server_info)
    {
        return true;
    }

    /**
     * 上传文件到 FastDFS
     *
     * @access pubic
     * @param string $local_filename 本地文件路径
     * @param string $file_ext_name 文件扩展名
     * @return array
     */
    public function storage_upload_by_filename(
        $local_filename,
        $file_ext_name = null,
        $meta_list = null,
        $group_name = 'G01',
        $tracker_server = null,
        $storage_server = null)
    {
        if (is_array($tracker_server) && count($tracker_server)) {
            $tracker = new FastDFSTracker($tracker_server['ip_addr'], $tracker_server['port']);
        } else {
            $tracker = $this->tracker;
        }

        if (! is_array($storage_server)) {
            $storage_server = $tracker->applyStorage($group_name);
        }

        if (! is_null($file_ext_name)) {
            $local_filename .= '.' . $file_ext_name;
        }

        $storage = new FastDFSStorage($storage_server['storage_addr'], $storage_server['storage_port']);
        $result = $storage->uploadFile($storage_server['storage_index'], $local_filename);

        if (is_array($meta_list)) {
            $storage->setFileMetaData($result['group_name'], $result['filename'], $meta_list);
        }

        return $result;
    }

    /**
     * 删除文件
     *
     * @access pubic
     * @param string $group_name 远程文件所属组名
     * @param string $remote_filename 远程文件名
     * @return booleam
     */
    public function storage_delete_file($group_name, $remote_filename, $tracker_server = null, $storage_server = null)
    {
        if (is_array($tracker_server) && count($tracker_server)) {
            $tracker = new FastDFSTracker($tracker_server['ip_addr'], $tracker_server['port']);
        } else {
            $tracker = $this->tracker;
        }

        if (! is_array($storage_server)) {
            $storage_server = $tracker->applyStorage($group_name);
        }

        $storage = new FastDFSStorage($storage_server['storage_addr'], $storage_server['storage_port']);
        return $storage->deleteFile($group_name, $remote_filename);
    }

    /**
     * 读取文件信息
     *
     * @access public
     * @param string $group_name 远程文件所属组名
     * @param string $remote_filename 远程文件名
     * @return array
     */
    public function get_file_info($group_name, $filename)
    {
        $storage_server = $this->tracker->applyStorage($group_name);

        $storage = new FastDFSStorage($storage_server['storage_addr'], $storage_server['storage_port']);
        return $storage->getFileInfo($group_name, $filename);
    }
}