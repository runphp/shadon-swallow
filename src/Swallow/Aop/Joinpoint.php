<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Aop;

/**
 * Aop被调用插入点
 * 
 * @author     SpiritTeam
 * @since      2015年1月13日
 * @version    1.0
 */
class Joinpoint
{

    /**
     * 类对象
     * @var object
     */
    private $class = null;

    /**
     * 类名
     * @var string
     */
    private $className = null;

    /**
     * 类命名空间切成数组
     * @var array
     */
    private $classPath = null;

    /**
     * 当前执行的操作名
     * @var string
     */
    private $method = null;

    /**
     * 参数
     * @var array
     */
    private $args = null;

    /**
     * 返回值
     * @var mixed
     */
    private $returnValue = null;

    /**
     * 是否执行过原方法体
     * @var boolean
     */
    private $isProcessCalled = false;

    /**
     * 调用原方法的回调
     * @var callable
     */
    private $processCall = null;

    /**
     * 构造
     * 
     * @param object $class
     */
    public function __construct($class)
    {
        $this->class = $class;
        $this->className = get_class($class);
        $this->className = substr($this->className, strpos($this->className, '\\') + 1);
        $class->__JoinPoints = $this;
    }

    /**
     * 设置方法
     * 
     * @param string $method
     * @return self
     */
    public function setMethodName($method)
    {
        $this->method = $method;
        return $this;
    }

    /**
     * 设置参数
     * 
     * @param array $args
     * @param mixed $val
     * @return self
     */
    public function setArgs($args, $val = '')
    {
        if (is_array($args)) {
            $this->args = $args;
        } else {
            $this->args[$args] = $val;
        }
        return $this;
    }

    /**
     * 设置代理处理方法
     * 
     * @param callable $args
     * @return self
     */
    public function setProcess(callable $callback)
    {
        $this->isProcessCalled = false;
        $this->processCall = $callback;
        return $this;
    }

    /**
     * 设置返回值 
     * 
     * @param array $returnValue
     * @return self
     */
    public function setReturnValue($returnValue)
    {
        $this->returnValue = $returnValue;
        return $this;
    }

    /**
     * 获取参数
     * 
     * @param  string $name
     * @return mixed
     */
    public function getArgs($name = '')
    {
        if (empty($name)) {
            return $this->args;
        } elseif (is_string($name)) {
            return $this->args[$name];
        } elseif (is_numeric($name)) {
            return array_values($this->args)[$name];
        }
    }

    /**
     * 获取返回值
     * 
     * @return mixed
     */
    public function getReturnValue()
    {
        return $this->returnValue;
    }

    /**
     * 获取类名
     * 
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * 获取方法
     * 
     * @return string
     */
    public function getMethodName()
    {
        return $this->method;
    }

    /**
     * 获取类名数组
     * 
     * @return array
     */
    public function getClassPath()
    {
        return isset($this->classPath) ? $this->classPath : ($this->classPath = explode('\\', $this->className));
    }

    /**
     * 获取类属性
     *
     * @param  string $name
     * @return mixed
     */
    public function getProperty($name)
    {
        return isset($this->class->$name) ? $this->class->$name : null;
    }

    /**
     * 设置已执行过
     *
     * @param array $args
     */
    public function setProcessCalled($status = true)
    {
        $this->isProcessCalled = $status;
    }

    /**
     * 是否运行过
     *
     * @param array $args
     * @return self
     */
    public function isCalled()
    {
        return $this->isProcessCalled;
    }

    /**
     * 执行代理方法
     * 
     * @param array $args
     * @return self
     */
    public function process()
    {
        $this->isProcessCalled = true;
        $call = $this->processCall;
        $this->returnValue = $call($this->args);
        return $this;
    }
}