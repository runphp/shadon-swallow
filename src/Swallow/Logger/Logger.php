<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Logger;

use Phalcon\Logger\Adapter\File as FileAdapter;
use Phalcon\Logger\Formatter\Line as LineFormatter;

/**
 * 日志记录
 *
 * @author     SpiritTeam
 * @since      2015年8月13日
 * @version    1.0
 */
class Logger extends \Phalcon\Logger
{

    private $name = '';

    private $dir = '';

    private $message = [];

    private $isReplace = false;

    public function __construct()
    {
        $this->dir = ROOT_PATH . '/temp/log';
    }

    /**
     * 写入日志后初始化
     *
     * @author 范世军<fanshijun@eelly.net>
     * @since  2016年1月28日
     */
    private function destruct()
    {
        $this->name = '';
        $this->message = [];
        $this->isReplace = false;
    }

    /**
     * 日志类型
     *
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年9月9日
     */
    public function systemType()
    {
        return [
            'SPECIAL' => self::SPECIAL,
            'CUSTOM' => self::CUSTOM,
            'DEBUG' => self::DEBUG,
            'INFO' => self::INFO,
            'NOTICE' => self::NOTICE,
            'WARNING' => self::WARNING,
            'ERROR' => self::ERROR,
            'ALERT' => self::ALERT,
            'CRITICAL' => self::CRITICAL,
            'EMERGENCE' => self::EMERGENCE,
            'EMERGENCY' => self::EMERGENCY];
    }

    /**
     * 返回日志目录
     *
     * @return string
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年9月9日
     */
    public function getDir()
    {
        return $this->dir;
    }

    /**
     * 设置日志目录  在$this->dir目录之下建立子目录
     *
     * @param string $dir
     * @return resource
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年9月9日
     */
    public function setDir($dir)
    {
        $this->dir = $dir;
        return $this;
    }

    /**
     * 设置日志文件名
     *
     * @param string $name
     * @return resource
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年9月9日
     */
    public function setName($name)
    {
        $this->name = ! empty($name) ? $name.'.log' : $this->name;
        return $this;
    }

    /**
     * 设置日志文件是否每次生成都替换原有内容
     *
     * @param bool $isReplace
     * @return resource
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年9月9日
     */
    public function setReplace($isReplace = false)
    {
        $this->isReplace = $isReplace === true ? $isReplace : false;
        return $this;
    }

    /**
     * 获取类型
     *
     * @param string $type
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年9月9日
     */
    public function getType($type)
    {
        if (! empty($type)) {
            $sysType = $this->systemType();
            $type = strtoupper($type);
            $type = isset($sysType[$type]) ? $sysType[$type] : self::DEBUG;
        } else {
            $type = self::DEBUG;
        }
        return $type;
    }

    /**
     * log
     *
     * @param string $message
     * @param string $type
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年9月9日
     */
    public function log($message, $type = 'DEBUG')
    {
        $dir = $this->dir;
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $typeNum = $this->getType($type);
        $this->name = $this->name ? $this->name : date('Y.m.d').'.log';
        $path = $dir . '/' . $this->name;
        $options = null;
        if($this->isReplace){
            $options = ['mode' => 'w+'];
        }
        $logger = new FileAdapter($path, $options);
        // 修改日志格式
        $date = date("Y-m-d H:i:s");
        $formatter = new LineFormatter("[$date][$type]\n%message%");
        $logger->setFormatter($formatter);
        $logger->log($message, $typeNum);
        $this->destruct();
    }

    /**
     * 手动记录日志
     *
     * @param string $message
     * @param string $type
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年9月9日
     */
    public function record($message, $type = 'DEBUG')
    {
        $this->message[] = ['type' => $type, 'message' => $message];
        return $this;
    }

    /**
     * 写入日志
     *
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年9月9日
     */
    public function save()
    {
        if(empty($this->message)){
            return false;
        }
        $dir = $this->dir;
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $this->name = $this->name ? $this->name : date('Y.m.d').'.log';
        $path = $dir . '/' . $this->name;
        if($this->isReplace){
            file_exists($path) && unlink($path);
        }
        $logger = new FileAdapter($path);
        $logger->begin();
        foreach ($this->message as $message){
            $typeNum = $this->getType($message['type']);
            $logger->log($message['message'], $typeNum);
        }
        // 修改日志格式
        $date = date("Y-m-d H:i:s");
        $formatter = new LineFormatter("[$date][".$message['type']."]\n%message%");
        $logger->setFormatter($formatter);
        $logger->commit();
        $this->destruct();
    }
}
