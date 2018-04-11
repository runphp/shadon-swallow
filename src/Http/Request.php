<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Http;

use Swallow\Traits\FastDfsObj;

/**
 * 请求
 *
 * @author     SpiritTeam
 * @since      2015年8月13日
 * @version    1.0
 */
class Request extends \Phalcon\Http\Request
{
    
    use FastDfsObj;

    /**
     * 默认文件类型
     * @var string
     */
    private $fileType = 'png|gif|jpg|jpeg';

    /**
     * 默认文件大小
     * @var string
     */
    private $fileSize = 2097152;

    /**
     * 默认文件像素
     * @var string
     */
    private $fileWidth = 800;

    /**
     * 错误
     * @var string
     */
    public $error = '';

    /**
     * Gets attached files as Swallow\Http\Request\File instances
     *
     * @param bool $onlySuccessful 
     * @return \Swallow\Http\Request\File 
     */
    public function getUploadedFiles($onlySuccessful = false)
    {
        $di = $this->getDI();
        $files = [];
        $superFiles = $_FILES;
        if (count($superFiles) > 0) {
            foreach ($superFiles as $prefix => $input) {
                if (is_array($input["name"])) {
                    $smoothInput = $this->smoothFiles($input["name"], $input["type"], $input["tmp_name"], $input["size"], $input["error"], 
                        $prefix);
                    foreach ($smoothInput as $file) {
                        if ($file["error"] !== UPLOAD_ERR_OK) {
                            $this->error = '上传失败，错误码：' . $file["error"];
                            return false;
                        }
                        if ($onlySuccessful == false || $file["error"] == UPLOAD_ERR_OK) {
                            $dataFile = [
                                "name" => $file["name"], 
                                "type" => $file["type"], 
                                "tmp_name" => $file["tmp_name"], 
                                "size" => $file["size"], 
                                "error" => $file["error"]];
                            //$fileObj = new File($dataFile, $file["key"]);
                            $filesObj = $di->get('\Swallow\Http\Request\File', [$dataFile, $file["key"]]);
                            $files[] = $filesObj;
                            $isAllowd = $this->isAllowd($filesObj);
                            if ($isAllowd == false) {
                                return false;
                            }
                        }
                    }
                } else {
                    if ($input["error"] != UPLOAD_ERR_NO_FILE) {
                        if ($input["error"] !== UPLOAD_ERR_OK) {
                            $this->error = '上传失败，错误码：' . $input["error"];
                            return false;
                        }
                        if ($onlySuccessful == false || $input["error"] == UPLOAD_ERR_OK) {
                            //$files[] = new File($input, $prefix);
                            $filesObj = $di->get('Swallow\Http\Request\File', [$input, $prefix]);
                            $files[] = $filesObj;
                            $isAllowd = $this->isAllowd($filesObj);
                            if ($isAllowd == false) {
                                return false;
                            }
                        }
                    }
                }
            }
        }
        return $files;
    }

    /**
     * 获取错误
     *
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * 判断允许上传文件
     *
     * @param object $filesObj
     * @return booleam
     */
    function isAllowd($filesObj)
    {
        $extension = $filesObj->getExtension();
        $filesName = $filesObj->getName();
        if (! $this->isAllowdType($extension)) {
            $this->error = '您上传的文件：'.$filesName.'，格式不正确！';
            return false;
        }
        $size = $filesObj->getSize();
        if (! $this->isAllowdSize($size)) {
            $this->error = '您上传的文件：'.$filesName.'，大小超过了允许值:' . $this->fileSize / 1024 . 'k！';
            return false;
        }
//         $imagesize = $filesObj->getImageSize();
//         if (! $this->isAllowdWidth($imagesize[0])) {
//             $this->error = '您上传的文件：'.$filesName.'，像素宽度超过了允许值:' . $this->fileWidth . 'px！';
//             return false;
//         }
        return true;
    }

    /**
     * 判断指定类型文件是否容许上传
     *
     * @param string $type 文件类型
     * @return booleam
     */
    function isAllowdType($type)
    {
        if (! $this->fileType) {
            return true;
        }
        $fileType = explode("|", $this->fileType);
        return in_array(strtolower($type), $fileType);
    }

    /**
     * 判断是否容许上传指定大小的文件
     *
     * @param integer $size 文件大小
     * @return booleam
     */
    function isAllowdSize($size)
    {
        if (! $this->fileSize) {
            return true;
        }
        return is_numeric($this->fileSize) ? ($size <= $this->fileSize) : ($size >= $this->fileSize[0] && $size <= $this->fileSize[1]);
    }

    /**
     * 判断是否容许上传指定像素宽度的文件
     *
     * @param int $width 文件像素宽度
     * @return booleam
     */
    function isAllowdWidth($width)
    {
        if (! $this->fileWidth) {
            return true;
        }
        return $width <= $this->fileWidth;
    }

    /**
     * 设置限制文件类型
     *
     * @param string $type  （小写）示例：gif|jpg|jpeg|png
     */
    public function setFileType($type)
    {
        if (empty($type)) {
            return false;
        }
        $this->fileType = $type;
    }

    /**
     * 设置限制文件大小
     *
     * @param string|array $size 单位：字节byte
     */
    public function setFileSize($size)
    {
        if (empty($size)) {
            return false;
        }
        $this->fileSize = $size;
    }

    /**
     * 设置限制文件像素
     *
     * @param string $width 单位：px
     */
    public function setFileWidth($width)
    {
        if (empty($width)) {
            return false;
        }
        $this->fileWidth = $width;
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
        $objDfs = $this->getFastDfs();
        $fileInfo = explode('/', $filePath, 2);
        $fileMetaInfo = $objDfs->get_file_info($fileInfo[0], $fileInfo[1]);
        if (is_array($fileMetaInfo) && count($fileMetaInfo)) {
            return $objDfs->storage_delete_file($fileInfo[0], $fileInfo[1]);
        }
        return false;
    }
}
