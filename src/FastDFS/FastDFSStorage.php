<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\FastDFS;

class FastDFSStorage extends FastDFSBase
{

    /**
     * 上传文件
     *
     * @command 11
     * @param char $index 索引
     * @param string $filename
     * @param string $文件扩展名
     * @return array
     */
    public function uploadFile($index, $filename, $ext = '')
    {
        if (! file_exists($filename)) {
            return FALSE;
        }
        
        $path_info = pathinfo($filename);
        
        if (strlen($ext) > FDFS_FILE_EXT_NAME_MAX_LEN) {
            return $this->trigger_error('file ext too long.', 0);
        }
        
        if ($ext === '') {
            $ext = $path_info['extension'];
        }
        
        $fp = fopen($filename, 'rb');
        flock($fp, LOCK_SH);
        
        $filesize = filesize($filename);
        
        $req_body_length = 1 + FDFS_PROTO_PKG_LEN_SIZE + FDFS_FILE_EXT_NAME_MAX_LEN + $filesize;
        
        $req_header = self::buildHeader(11, $req_body_length);
        $req_body = pack('C', $index) . self::packU64($filesize) . self::padding($ext, FDFS_FILE_EXT_NAME_MAX_LEN);
        $this->send($req_header . $req_body);
        
        stream_copy_to_stream($fp, $this->_sock, $filesize);
        
        flock($fp, LOCK_UN);
        fclose($fp);
        
        $res_header = $this->read(FDFS_HEADER_LENGTH);
        $res_info = self::parseHeader($res_header);
        
        if ($res_info['status'] !== 0) {
            return $this->trigger_error('something wrong with uplode file', $res_info['status']);
        }
        
        $res_body = $res_info['length'] ? $this->read($res_info['length']) : FALSE;
        $group_name = trim(substr($res_body, 0, FDFS_GROUP_NAME_MAX_LEN));
        $file_path = trim(substr($res_body, FDFS_GROUP_NAME_MAX_LEN));
        
        return array('group_name' => $group_name, 'filename' => $file_path);
    }

    /**
     * 上传Slave文件
     *
     * @command 21
     * @param string $filename 待上传的文件名称
     * @param string $master_file_path 主文件名称
     * @param string $prefix_name 后缀的前缀名
     * @param string $ext 后缀名称
     * @return array/boolean
     */
    public function uploadSlaveFile($filename, $master_file_path, $prefix_name, $ext = '')
    {
        if (! file_exists($filename)) {
            return FALSE;
        }
        
        $path_info = pathinfo($filename);
        
        if (strlen($ext) > FDFS_FILE_EXT_NAME_MAX_LEN) {
            return $this->trigger_error('file ext too long.', 0);
        }
        
        if ($ext === '') {
            $ext = $path_info['extension'];
        }
        
        $fp = fopen($filename, 'rb');
        flock($fp, LOCK_SH);
        
        $filesize = filesize($filename);
        $master_file_path_length = strlen($master_file_path);
        
        $req_body_length = 16 + FDFS_FILE_PREFIX_MAX_LEN + FDFS_FILE_EXT_NAME_MAX_LEN + $master_file_path_length + $filesize;
        
        $req_header = self::buildHeader(21, $req_body_length);
        $req_body = pack('x4N', $master_file_path_length) . self::packU64($filesize) . self::padding($prefix_name, FDFS_FILE_PREFIX_MAX_LEN);
        $req_body .= self::padding($ext, FDFS_FILE_EXT_NAME_MAX_LEN) . $master_file_path;
        
        $this->send($req_header . $req_body);
        
        stream_copy_to_stream($fp, $this->_sock, $filesize);
        
        flock($fp, LOCK_UN);
        fclose($fp);
        
        $res_header = $this->read(FDFS_HEADER_LENGTH);
        $res_info = self::parseHeader($res_header);
        
        if ($res_info['status'] !== 0) {
            if ($res_info['status'] == 17) {
                $msg = 'targe slave file already existd';
            } else {
                $msg = 'something in upload slave file';
            }
            
            $this->trigger_error($msg, $res_info['status']);
            
            return FALSE;
        }
        
        $res_body = $res_info['length'] ? $this->read($res_info['length']) : FALSE;
        
        $group_name = trim(substr($res_body, 0, FDFS_GROUP_NAME_MAX_LEN));
        $file_path = trim(substr($res_body, FDFS_GROUP_NAME_MAX_LEN));
        
        return array('group_name' => $group_name, 'file_path' => $file_path);
    }

    /**
     * 删除文件
     *
     * @command 12
     * @param string $group_name 组名称
     * @param string $file_path 文件路径
     * @return boolean 删除成功与否
     */
    public function deleteFile($group_name, $file_path)
    {
        $req_body_length = strlen($file_path) + FDFS_GROUP_NAME_MAX_LEN;
        $req_header = self::buildHeader(12, $req_body_length);
        $req_body = self::padding($group_name, FDFS_GROUP_NAME_MAX_LEN) . $file_path;
        
        $this->send($req_header . $req_body);
        
        $res_header = $this->read(FDFS_HEADER_LENGTH);
        $res_info = self::parseHeader($res_header);
        
        return ! $res_info['status'];
    }

    /**
     * 获取文件元信息
     *
     * @command 15
     * @param string $group_name 组名称
     * @param string $file_path 文件路径
     * @return array 元信息数组
     */
    public function getFileMetaData($group_name, $file_path)
    {
        $req_body_length = strlen($file_path) + FDFS_GROUP_NAME_MAX_LEN;
        $req_header = self::buildHeader(15, $req_body_length);
        $req_body = self::padding($group_name, FDFS_GROUP_NAME_MAX_LEN) . $file_path;
        
        $this->send($req_header . $req_body);
        
        $res_header = $this->read(FDFS_HEADER_LENGTH);
        $res_info = self::parseHeader($res_header);
        
        if (! ! $res_info['status']) {
            return FALSE;
        }
        
        $res_body = $res_info['length'] ? $this->read($res_info['length']) : FALSE;
        
        return self::parseMetaData($res_body);
    }

    /**
     * 设置文件元信息
     *
     * @command 13
     * @param string $group_name 组名称
     * @param string $file_path 文件路径
     * @param array $meta_data 元信息数组
     * @return boolean 设置成功与否
     */
    public function setFileMetaData($group_name, $file_path, array $meta_data, $flag = FDFS_OVERWRITE_METADATA)
    {
        $meta_data = self::buildMetaData($meta_data);
        $meta_data_length = strlen($meta_data);
        $file_path_length = strlen($file_path);
        $flag = $flag === FDFS_OVERWRITE_METADATA ? 'O' : 'M';
        
        $req_body_length = (FDFS_PROTO_PKG_LEN_SIZE * 2) + 1 + $meta_data_length + $file_path_length + FDFS_GROUP_NAME_MAX_LEN;
        
        $req_header = self::buildHeader(13, $req_body_length);
        
        $req_body = self::packU64($file_path_length) . self::packU64($meta_data_length);
        $req_body .= $flag . self::padding($group_name, FDFS_GROUP_NAME_MAX_LEN) . $file_path . $meta_data;
        
        $this->send($req_header . $req_body);
        
        $res_header = $this->read(FDFS_HEADER_LENGTH);
        $res_info = self::parseHeader($res_header);
        
        return ! $res_info['status'];
    }

    /**
     * 下载文件(不建议对大文件使用)
     *
     * @command 14
     * @param string $group_name 组名称
     * @param string $file_path 文件路径
     * @param int $offset 下载文件偏移量
     * @param int $length 下载文件大小
     * @return string 文件内容
     */
    public function downloadFile($group_name, $file_path, $offset = 0, $length = 0)
    {
        $file_path_length = strlen($file_path);
        $req_body_length = (FDFS_PROTO_PKG_LEN_SIZE * 2) + $file_path_length + FDFS_GROUP_NAME_MAX_LEN;
        
        $req_header = self::buildHeader(14, $req_body_length);
        
        $req_body = self::packU64($offset) . self::packU64($length) . self::padding($group_name, FDFS_GROUP_NAME_MAX_LEN);
        $req_body .= $file_path;
        
        $this->send($req_header . $req_body);
        
        $res_header = $this->read(FDFS_HEADER_LENGTH);
        $res_info = self::parseHeader($res_header);
        
        if (! ! $res_info['status'])
            return FALSE;
        
        return $this->read($res_info['length']);
    }

    /**
     * 检索文件信息
     *
     * @command 22
     * @param string $group_name 组名称
     * @param string $file_path 文件路径
     * @return array
     */
    public function getFileInfo($group_name, $file_path)
    {
        $req_body_length = strlen($file_path) + FDFS_GROUP_NAME_MAX_LEN;
        $req_header = self::buildHeader(22, $req_body_length);
        $req_body = self::padding($group_name, FDFS_GROUP_NAME_MAX_LEN) . $file_path;
        
        $this->send($req_header . $req_body);
        
        $res_header = $this->read(FDFS_HEADER_LENGTH);
        $res_info = self::parseHeader($res_header);
        
        if (! ! $res_info['status'])
            return FALSE;
        
        $res_body = $res_info['length'] ? $this->read($res_info['length']) : FALSE;
        
        $file_size = self::unpackU64(substr($res_body, 0, FDFS_PROTO_PKG_LEN_SIZE));
        $timestamp = self::unpackU64(substr($res_body, FDFS_PROTO_PKG_LEN_SIZE, FDFS_PROTO_PKG_LEN_SIZE));
        list (, , $crc32) = unpack('N2', substr($res_body, 2 * FDFS_PROTO_PKG_LEN_SIZE, FDFS_PROTO_PKG_LEN_SIZE));
        $crc32 = base_convert(sprintf('%u', $crc32), 10, 16);
        $storage_id = trim(substr($res_body, - 16));
        
        return array('file_size' => $file_size, 'timestamp' => $timestamp, 'crc32' => $crc32, 'storage_id' => $storage_id);
    }
}