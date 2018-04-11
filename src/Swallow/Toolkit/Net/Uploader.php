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
 * 文件上传辅助类
 *
 * @author     何砚文
 * @since      2015年7月15日
 * @version    1.0
 */
class Uploader
{

    static $fileUri            = null;

    static $fileAbspath        = null;

    static $file               = null;

    static $allowedFileType    = null;

    static $allowedFileSize    = null;

    static $allowedFileWidth   = null;

    static $rootDir            = null;

    static $fastdfsConfig      = null;

    static private $dfsUploadList  = array();
    
    static private $lang           = array(
        'not_allowed_type'   => '您上传的文件格式不正确',
        'not_allowed_size'   => '您上传的文件大小超过了允许值',
        'not_allowed_width'  => '您上传的文件像素宽度超过了允许值',
        'dir_doesnt_exists'  => '上传文件存放目录无法创建，请联系站长检查相应权限'
    );

    static public function get_tmp_path()
    {
        return IMAGES_PATH . '/data/files/temp';
    }

    /**
     * 添加由POST上来的文件
     *
     * @param string $file 上传的文件
     * @return void
     */
    static function addFile($file, $fileType = '')
    {
        if (! is_uploaded_file($file['tmp_name']) && 'cli' != PHP_SAPI) {
            return false;
        }
        self::$file = self::getUploadedInfo($file, $fileType);
    }

    /**
     * 设定允许添加的文件类型
     *
     * @param string $type （小写）示例：gif|jpg|jpeg|png
     * @return void
     */
    static function allowedType($type)
    {
        self::$allowedFileType = explode('|', $type);
    }

    /**
     * 允许的大小
     *
     * @param mixed $size
     * @return void
     */
    static function allowedSize($size)
    {
        self::$allowedFileSize = $size;
    }

    /**
     * 允许的大小
     *
     * @param mixed $size
     * @return void
     */
    static function allowedWidth($width)
    {
        self::$allowedFileWidth = $width;
    }

    /**
     * 读取上传文件信息
     *
     * @param array $file 上传文件数组信息
     * @return array
     */
    static function getUploadedInfo($file, $fileType = '')
    {
        $pathinfo = pathinfo($file['name']);
        $file['extension'] = ! empty($fileType) ? strtolower($fileType) : strtolower($pathinfo['extension']); // 扩展名转换为小写
        $file['filename'] = $pathinfo['basename'];
        if (! self::$isAllowdType($file['extension'])) {
            self::_error(self::$lang['not_allowed_type'], $file['extension']);
            
            return false;
        }
        if (! self::isAllowdSize($file['size'])) {
            self::_error(self::$lang['not_allowed_size'] . self::$allowedFileSize / 1024 . 'K', self::$sizeFormat($file['size']));
            
            return false;
        }
        $imageSize = @getimagesize($file['tmp_name']);
        if (! self::$isAllowdWidth($imageSize[0])) {
            self::_error(self::$lang['not_allowed_width'], $imageSize[0]);
            return false;
        }
        
        return $file;
    }

    /**
     * 判断指定类型文件是否容许上传
     *
     * @param string $type 文件类型
     * @return booleam
     */
    static function isAllowdType($type)
    {
        if (! self::$allowedFileType) {
            return true;
        }
        return in_array(strtolower($type), self::$allowedFileType);
    }

    /**
     * 判断是否容许上传指定大小的文件
     *
     * @param integer $size 文件大小
     * @return booleam
     */
    static function isAllowdSize($size)
    {
        if (! self::$allowedFileSize) {
            return true;
        }
        
        return is_numeric(self::$allowedFileSize) ? ($size <= self::$allowedFileSize) : ($size >= self::$allowedFileSize[0] &&
             $size <= self::$allowedFileSize[1]);
    }

    /**
     * 判断是否容许上传指定像素宽度的文件
     *
     * @param int $width 文件像素宽度
     * @return booleam
     */
    static function isAllowdWidth($width)
    {
        if (! self::allowedFileWidth) {
            return true;
        }
        return self::allowedFileWidth > $width ? true : false;
    }

    /**
     * 获取上传文件的信息
     *
     * @param none
     * @return void
     */
    static function fileInfo()
    {
        return self::file;
    }

    /**
     * 若没有指定root，则将会按照所指定的path来保存，但是这样一来，所获得的路径就是一个绝对或者相对当前目录的路径，因此用Web访问时就会有问题，所以大多数情况下需要指定
     *
     * @param none
     * @return void
     */
    static function rootDir($dir)
    {
        self::$rootDir = $dir;
    }

    /**
     * 保存上传的文件
     *
     * @param string $dir 上传文件保存路径
     * @param string $name 上传文件名
     * @param booleam $autocompress 是否自动压缩图片
     * @param booleam $useDFS 是否将文件上传到分布式文件系统
     */
    static function save($dir, $name = false, $autocompress = false, $useDFS = false)
    {
        if (! is_array(self::$file) || ! file_exists(self::$file['tmp_name'])) {
            return false;
        }
        if (! $name) {
            $name = self::$file['filename'];
        } else {
            $name .= '.' . self::$file['extension'];
        }
        $path = $dir . '/' . $name;
        if (true == $useDFS) {
            $fdfs = self::getFastDFS();
            
            if (is_null($fdfs)) {
                $useDFS = false;
            } else {
                $old_root_path = self::rootDir;
                self::$rootDir = LOG_PATH . 'dfs';
                $path = basename($path);
            }
        }
        $uploadResult = self::moveUploadedile(self::$file['tmp_name'], $path, $autocompress);
        if (true == $useDFS && false != $uploadResult) {
            $tmpFile = self::rootDir . '/' . $uploadResult;
            $uploadResult = self::uploadFileFastdfs($tmpFile);
            self::$dfsUploadList[] = $tmpFile;
            
            if (! $uploadResult) {
                return false;
            }
        }
        
        self::$fileUri = $uploadResult;
        
        self::logUploadFile('fileupload');
        return $uploadResult;
    }

    /**
     * 将上传的文件移动到指定的位置
     *
     * @param string $src 源文件路径
     * @param string $target 上传后保存路径
     * @return string/booleam 上传成功返回上传的文件路径，失败返回 false
     */
    static function moveUploadedile($src, $target, $autocompress)
    {
        self::$fileAbspath = $absPath = self::rootDir ? self::rootDir . '/' . $target : $target;
        if (! ecm_mkdir(dirname($absPath))) {
            self::_error(self::$lang['dir_doesnt_exists']);
            
            return false;
        }
        if ($autocompress) {
            $data = @getimagesize($src);
            if (! empty($_GET['w']) && ! empty($_GET['h'])) {
                $_pic_w = $data[0] > intval($_GET['w']) ? intval($_GET['w']) : $data[0];
                $_pic_h = $data[1] > intval($_GET['h']) ? intval($_GET['h']) : $data[1];
                import('image.func');
                make_thumb($src, $absPath, $_pic_w, $_pic_h);
                return $target;
            } elseif (! empty($_GET['w']) && empty($_GET['h'])) {
                $_pic_w = $data[0] > intval($_GET['w']) ? intval($_GET['w']) : $data[0];
                import('image.func');
                make_thumb($src, $absPath, $_pic_w);
                return $target;
            } elseif ($data[0] > 800) {
                import('image.func');
                make_thumb($src, $absPath, 800);
                return $target;
            } elseif (move_uploaded_file($src, $absPath)) {
                return $target;
            } else {
                return false;
            }
        } else {
            if (move_uploaded_file($src, $absPath)) {
                return $target;
            } else {
                if (rename($src, $absPath)) {
                    return $target;
                }
                return false;
            }
        }
    }

    /**
     * 上传文件到 FastDFS
     *
     * @param string $file 需要上传的文件路径
     * @return string 上传后的文件ID
     */
    static function uploadFileFastdfs($file)
    {
        self::$fileUri = $file;
        $fdfs = self::getFastDFS();
        $fastdfs = get_config('fastdfs');
        $n = array_rand($fastdfs['group']);
        $dfsgroup = $fastdfs['group'][$n];
        $result = $fdfs->storage_upload_by_filename($file, null, array(), $dfsgroup);
        $uploadResult = is_array($result) && isset($result['filename']) ? $result['group_name'] . '/' . $result['filename'] : false;
        if (! defined('APP')) {
            define('APP', 'none');
            define('ACT', 'none');
        }
        $msg = $_SERVER["REQUEST_TIME"] . ' add ' . APP . ':' . ACT . ' ' . $dfsgroup . ' ' . $file . ' ' . $uploadResult . "\n";
        $logfile = LOG_PATH . 'file_upload_dfs_' . date('Ym') . '.txt';
        error_log($msg, 3, $logfile);
        
        return $uploadResult;
    }

    /**
     * 删除上传的图片
     *
     * @param string $file 要删除的文件
     * @return booleam
     */
    static function deleteUploadFile($file)
    {
        $msg = $_SERVER["REQUEST_TIME"] . ' del ' . APP . ':' . ACT . ' ' . $file . "\n";
        $logfile = LOG_PATH . 'file_upload_dfs_del_' . date('Ym') . '.txt';
        
        error_log($msg, 3, $logfile);
        
        if ('G' == substr($file, 0, 1)) {
            $objDfs = self::getFastDfs();
            $fileInfo = explode('/', $file, 2);
            $fileMetaInfo = $objDfs->get_file_info($fileInfo[0], $fileInfo[1]);
            if (is_array($fileMetaInfo) && count($fileMetaInfo)) {
                return $objDfs->storage_delete_file($fileInfo[0], $fileInfo[1]);
            }
            return false;
        } else {
            $file = IMAGES_PATH . '/' . $file;
            if (is_file($file)) {
                return unlink($file);
            }
            return false;
        }
    }

    /**
     * 获取文件大小
     *
     * @param string $file 要获取的文件
     * @return booleam
     */
    static function getFileSize($file)
    {
        if ('G' == substr($file, 0, 1)) {
            $objDfs = self::getFastDfs();
            $fileInfo = explode('/', $file, 2);
            $fileMetaInfo = $objDfs->get_file_info($fileInfo[0], $fileInfo[1]);
            return $fileMetaInfo['file_size'];
        } else {
            $filesize = filesize(IMAGES_PATH . $file);
            return $filesize;
        }
    }

    /**
     * 检查 FastDFS 是否可用
     *
     * @return booleam
     */
    static function getDFSStatus()
    {
        self::$fastdfsConfig = $dfsConfig = get_config('fastdfs', 'system');
        
        if (is_array($dfsConfig) && isset($dfsConfig['status']) && true === $dfsConfig['status']) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取 FastDFS 对象
     *
     * @return object
     */
    static function getFastDFS()
    {
        static $objDfs = null;
        
        if (is_null($objDfs)) {
            self::$fastdfsConfig = $dfsConfig = get_config('fastdfs', 'system');
            if (is_array($dfsConfig) && isset($dfsConfig['status']) && true == $dfsConfig['status']) {
                if (! class_exists('FastDFS', false)) {
                    include (ROOT_PATH . '/eccore/compatible/FastDFS.php');
                }
                
                $objDfs = new \FastDFS();
                $objDfs->connect_server($dfsConfig['trackerIp'], $dfsConfig['trackerPort']);
            }
        }
        
        return $objDfs;
    }

    /**
     * 记录文件上传日志
     *
     * @param string $cate 上传文件分类
     * @return void
     */
    static function logUploadFile($cate)
    {
        if (! defined('APP')) {
            define('APP', 'none');
            define('ACT', 'none');
        }
        $msg = date('Y-m-d H:i:s') . ' ' . APP . ':' . ACT . ' ' . $cate . ' ' . self::fileUri . "\n";
        $logfile = LOG_PATH . 'file_upload_' . date('Ym') . '.log';
        error_log($msg, 3, $logfile);
    }

    /**
     * 读取文件大小
     *
     * @return array
     */
    static function getUploadFileMeta()
    {
        if (file_exists(self::_file_abspath)) {
            return getimagesize(self::_file_abspath);
        } else {
            return false;
        }
    }

    /**
     * 生成随机的文件名
     */
    static function randomFilename()
    {
        $seedstr = explode(" ", microtime(), 5);
        $seed = $seedstr[0] * 10000;
        srand($seed);
        $random = rand(1000, 10000);
        
        return date("YmdHis", time()) . $random;
    }

    /**
     * 返回友好的文件大小
     *
     * @param integer $filesize 文件大小
     * @return string
     */
    static function sizeFormat($filesize)
    {
        if ($filesize >= 1073741824)
            $filesize = round($filesize / 1073741824 * 100) / 100 . ' GB';
        elseif ($filesize >= 1048576)
            $filesize = round($filesize / 1048576 * 100) / 100 . ' MB';
        elseif ($filesize >= 1024)
            $filesize = round($filesize / 1024 * 100) / 100 . ' KB';
        else
            $filesize = $filesize . ' Bytes';
        return $filesize;
    }

    public function __destruct()
    {
        if (count(self::dfsUploadList)) {
            foreach (self::dfsUploadList as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }
    }
    
    /**
     * 触发错误
     *
     * @author    Garbin
     * @param     string $errmsg
     * @return    void
     */
    static function _error($msg, $obj = ''){
        if(is_array($msg)){
            self::$_errors = array_merge(self::$_errors, $msg);
            self::$_errnum += count($msg);
        }else{
            self::$_errors[] = compact('msg', 'obj');
            self::$_errnum++;
        }
    }
}