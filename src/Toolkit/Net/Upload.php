<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Toolkit\Net;

/**
 * Upload包装类
 *
 * @author     SpiritTeam
 * @since      2015年1月13日
 * @version    1.0
 */
class Upload
{

    /**
     * 默认文件类型
     * @var string
     */
    private  $fileType = 'png|gif|jpg|jpeg';

    /**
     * 默认文件大小
     * @var string
     */
    private  $fileSize = 2097152;

    /**
     * 默认文件像素
     * @var string
     */
    private  $fileWidth = 800;

    /**
     * 实例
     * @var \uploader.lib
     */
    private $upload = null;

    /**
     * 获取实例
     *
     * @return self
     */
    public static function getInstance()
    {
        static $obj = null;
        if (! isset($obj)) {
            import('uploader.lib'); //导入上传类
            $obj = new self(new \Uploader());
        }
        return $obj;
    }

    /**
     * 构造
     *
     * @param \Uploaders $upload
     */
    private function __construct($upload)
    {
        $this->upload = $upload;
    }

    /**
     * 保存图片
     *
     * @param   string | array  $files  文件名称　| 文件信息
     * @param   boolean         $useDFS 是否将文件上传到分布式文件系统       
     * 
     * @return  string
     */
    public function save($file, $useDFS = true)
    {
        // 数据校验
        if (empty($file) && ! isset($file)) {
            return false;
        }
        
        // 根据文件信息上传
        if (is_array($file)){
            $this->upload->addFile($file);
            return $this->upload->save($this->upload->get_tmp_path(), date('YmdHis', time()), false, $useDFS);
            
        // 直接将服务器中的文件上传到FastDFS
        }else {
            return $this->upload->upload_file_fastdfs($file);
        }
    }

    /**
     * 删除文件
     *
     * @param string $filePath
     * @return boolean
     */
    public function deleteFile($filePath)
    {
        if (empty($filePath)) {
            return false;
        }
        return $this->upload->delete_upload_file($filePath);
    }
    
    /**
     * 下载DFS上传的文件
     * 
     * @param   string  $filePath   下载的文件路径
     * @return  boolean
     * 
     * @author  何砚文<heyanwen@eelly.net>
     * @since   2015-11-30
     */
    public function downloadFile($filePath)
    {
        if (empty($filePath)){
            return false;
        }
        return $this->upload->download_upload_file($filePath);
    }

    /**
     * 设置限制文件类型
     *
     * @param string $type  （小写）示例：gif|jpg|jpeg|png
     */
    public function setFileType($type)
    {
        $type = empty($type) ? $this->fileType : $type;
        $this->upload->allowed_type($type);
        return $this;
    }

    /**
     * 设置限制文件大小
     *
     * @param string $size 单位：字节byte
     */
    public function setFileSize($size)
    {
        $size = empty($size) ? $this->fileSize : $size;
        $this->upload->allowed_size($size);
        return $this;
    }
    
    /**
     * 指定root
     *
     * @return string
     */
    public function rootDir($dir)
    {
        $this->upload->_root_dir = $dir;
        return $this;
    }
    
    /**
     * 设置限制文件像素
     *
     * @param string $width 单位：px
     */
    public function setFileWidth($width)
    {
        $sysWidth = empty($width) ? $this->fileWidth : $width;
        $this->upload->allowed_width($sysWidth);
        return $this;
    }

    /**
     * 获取错误
     *
     * @return string
     */
    public function getError()
    {
       return $this->upload->get_error();
    }
    
    public function getTmpPath()
    {
        return $this->upload->get_tmp_path();
    }

}