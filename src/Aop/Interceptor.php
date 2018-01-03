<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Aop;

use Swallow\Traits\Singleton;

/**
 * Swallow 静态拦截器
 * 
 * @author     SpiritTeam
 * @since      2015年1月9日
 * @version    1.0
 */
class Interceptor
{
    use Singleton;

    /**
     * 装载的对象
     * @var string
     */
    private $interceptObj = null;

    /**
     * 代理选项
     * @var int
     */
    private $option = 0;

    /**
     * 构造方法
     * 
     * @param string $class
     */
    public function __construct($class)
    {
        $this->interceptObj = $class;
        $this->option = Option::SKIP_PROTECTED_METHOD | Option::SKIP_PRIVATE_METHOD;
    }

    /**
     * 设置代理参数
     * 
     * @param int $option
     * @return self
     */
    public function setOption($option)
    {
        $this->option = $option;
        return $this;
    }

    /**
     * 实例化对象
     * 
     * @return mixed
     */
    public function newInstance()
    {
        return empty($this->interceptObj) ? false : Proxy::getInstance($this->interceptObj, array(), $this->option);
    }

    /**
     * 带参数实例化对象
     * 
     * @param array $args
     * @return mixed
     */
    public function newInstanceArgs(array $args)
    {
        return empty($this->interceptObj) ? false : Proxy::getInstance($this->interceptObj, $args, $this->option);
    }
}