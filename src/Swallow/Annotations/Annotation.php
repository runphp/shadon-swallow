<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Annotations;

use Swallow\Traits\Singleton;
use Swallow\Core\Reflection;
use Swallow\Exception\StatusCode;

/**
 * Annotation主类
 *
 * @author     SpiritTeam
 * @since      2015年1月15日
 * @version    1.0
 */
class Annotation
{
    use Singleton;

    /**
     * 所有注解数据
     * @var array
     */
    private $data = array();

    /**
     * 方法合集
     * @var array
     */
    private $methods = array();

    /**
     * 注解构造函数
     *
     * @param  string $className
     */
    public function __construct($className)
    {
        $class = Reflection::getClass($className);
        /* $mtime = filemtime($class->getFileName());
        $ftime = filemtime(__FILE__);
        $mtime = $ftime > $mtime ? $ftime : $mtime;
        $key = $className . '_' . $mtime; */
        $key = $className . '_Annotation';
        $this->data = Cache::get($key);
        if (false === $this->data) {
            $this->data = Parse::init($class);
            Cache::set($key, $this->data);
        }
        foreach ($this->data as $key => $value) {
            $this->methods[$key] = new Method($key, $value);
        }
    }

    /**
     * 获取函数
     *
     * @return array<Method>
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * 获取函数
     *
     * @param  string $methodName
     * @return Method
     */
    public function getMethod($methodName)
    {
        if (isset($this->methods[$methodName])) {
            return $this->methods[$methodName];
        }
        throw new \Swallow\Exception\SystemException('Not found method: '.$methodName, StatusCode::REQUEST_NOT_FOUND);
    }
}
