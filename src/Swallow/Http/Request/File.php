<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Http\Request;

use Phalcon\Exception;
use Swallow\Traits\FastDfsObj;

/**
 * 文件上传
 *
 * @author     SpiritTeam
 * @since      2015年8月13日
 * @version    1.0
 */
class File extends \Phalcon\Http\Request\File implements \Phalcon\DI\InjectionAwareInterface
{

    use FastDfsObj;
    
    protected $dfsTempList = [];

    /**
     * @var \Phalcon\DiInterface
     */
    private $_dependencyInjector = null;

    /**
     * Sets the dependency injector
     *
     * @param mixed $dependencyInjector
     */
    public function setDI(\Phalcon\DiInterface $dependencyInjector)
    {
        $this->_dependencyInjector = $dependencyInjector;
    }

    /**
     * Returns the internal dependency injector
     *
     * @return \Phalcon\DiInterface
     */
    public function getDI()
    {
        return $this->_dependencyInjector;
    }
    
    /**
     * 返回dfs临时文件目录
     * 
     * @return string
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年9月9日
     */
    public function getTmpPath()
    {
        return ROOT_PATH . '/temp/log/dfs';
    }

    /**
     * 保存文件
     * @params array $opts 保存文件时候的选项
     * @return string
     *
     * @modify xulei<xulei@eelly.net> 增加webp格式图片支持
     */
    public function save($opts = array())
    {
        $fileName = $this->getName();
        if (empty($fileName)) {
            return false;
        }
        $rootDir = $this->getTmpPath();
        // 自动创建目录
        if (! is_dir($rootDir)) {
            mkdir($rootDir, 0755, true);
        }

        $filePath = $rootDir . '/' . $this->getRandomName();
        $uploadResult = $this->moveTo($filePath);
        if (false != $uploadResult) {
            // 转换webp格式的图片
            if (isset($opts['convert_webp']) && $opts['convert_webp'] == true) {
                $this->convertWebp($filePath);
            }

            $dfsPath = $this->uploadFileFastdfs($filePath);
            $this->dfsTempList[] = $filePath;
            if (! $dfsPath) {
                return false;
            }
        }
        return $dfsPath;
    }

    /**
     * 转换webp格式的图片
     *
     * @param string $file 需要转换的文件
     * @return bool 是否转换成功
     */
    public function convertWebp($filePath)
    {
        if (extension_loaded('imagick')) {
            try {
                $img = new \Imagick($filePath);
                $format = $img->identifyFormat('%m');
                if ($format == 'WEBP') {
                    unlink($filePath);
                    $img->setImageFormat ("jpeg");
                    $img->writeImage($filePath);
                    $img->destroy();
                    return true;
                }
            } catch (\ImagickException $ex) {
                if ($log = $this->getDI()->getShared('log')) {
                    $log->log($ex->getMessage(), 'ERROR');
                }
                return false;
            }
        }
        return false;
    }

    /**
     * 上传文件到 FastDFS
     *
     * @param string $file 需要上传的文件路径
     * @return string 上传后的文件ID
     */
    public function uploadFileFastdfs($file)
    {
        $fdfs = $this->getFastDFS();
        if (is_null($fdfs)) {
            return false;
        }
        $n = array_rand($this->config['group']);
        $dfsgroup = $this->config['group'][$n];
        $result = $fdfs->storage_upload_by_filename($file, null, array(), $dfsgroup);
        $uploadResult = is_array($result) && isset($result['filename']) ? $result['group_name'] . '/' . $result['filename'] : false;
        return $uploadResult;
    }

    /**
     * 生成随机的文件名
     */
    public function getRandomName()
    {
        return date("YmdHis", time()) . rand(1000, 10000) . '.' . $this->getExtension();
    }
    
    /**
     * 取得图像大小
     */
    public function getImageSize()
    {
        return @getimagesize($this->getTempName());
    }
    
    /**
     * Moves the temporary file to a destination within the application
     */
    public function moveTo($destination)
    {
        if (empty($destination)) {
            return false;
        }
        if (move_uploaded_file($this->_tmp, $destination)) {
            return true;
        } else {
            return rename($this->_tmp, $destination);
        }
    }

    /**
     * 删除临时文件
     * 
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年9月8日
     */
    public function __destruct()
    {
        if (count($this->dfsTempList)) {
            foreach ($this->dfsTempList as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }
    }
}
