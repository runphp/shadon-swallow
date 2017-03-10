<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Toolkit;

use Swallow\Exception\StatusCode;

/**
 * 文件上传调用类
 * 
 * @author    陈景贵<chenjinggui@eelly.net>
 * @since     2015年11月16日
 * @version   1.0
 */
class FileUploader
{

    public static $di;

    /**
     * getInstance
     * 
     * @return self
     * 
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年11月16日
     */
    public static function getInstance()
    {
        self::$di = \Phalcon\Di::getDefault();
        return self::$di->getShared('Swallow\Toolkit\FileUploader');
    }

    /**
     * 文件上传
     *
     * @return  string
     * @author  陈景贵<chenjinggui@eelly.net>
     * @since   2015年11月16日
     */
    public function upload()
    {
        $request = self::$di->getRequest();
        $retval = array('status' => StatusCode::OK, 'info' => '', 'retval' => null);
        if ($request->hasFiles()) {
            $uploadedFiles = $request->getUploadedFiles();
            if ($uploadedFiles !== false) {
                $imgUrl = [];
                foreach ($uploadedFiles as $file) {
                    $imgUrl[] = $file->save();
                }
                $retval['retval'] = $imgUrl;
            } else {
                $retval['status'] = StatusCode::BAD_REQUEST;
                $retval['info'] = $request->getError();
            }
        } else {
            $retval['status'] = StatusCode::BAD_REQUEST;
            $retval['info'] = 'Request parameter error!';
        }
        return $retval;
    }
}

