<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Exception;

/**
 * 服务层的异常.
 *
 * 用于网络传输，可进行序列化
 *
 * @author    何辉<hehui@eely.net>
 * @since     2015年9月16日
 * @version   1.0
 */
class ServiceException extends \RuntimeException implements \Serializable
{
    protected $extend;

    public function __construct($previous)
    {
        parent::__construct($previous->getMessage(), $previous->getCode(), $previous);
        $this->extend = $previous->__toString();
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        $previous = $this->getPrevious();
        return serialize([
            'message' => $this->message,
            'code' => $this->code,
            'file' => $previous->file,
            'line' => $previous->line,
            'extend' => $this->extend,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $this->message = $data['message'];
        $this->code = $data['code'];
        $this->file = $data['file'];
        $this->line = $data['line'];
        $this->extend = $data['extend'];
    }
}