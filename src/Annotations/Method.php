<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Annotations;

/**
 * 注册方法，获取注解内容用
 *
 * @author     SpiritTeam
 * @since      2015年1月15日
 * @version    1.0
 */
class Method
{

    /**
     * 函数名
     * @var string
     */
    public $name = '';

    /**
     * 注解数据
     * @var array
     */
    private $data = array();

    /**
     * 初始化
     * 
     * @param string|array $data
     */
    public function __construct($name, array $data)
    {
        $this->name = $name;
        $this->data = $data;
    }

    /**
     * 读取数据
     * 
     * @param  string $name
     * @return string
     */
    public function getAttr($name = '')
    {
        return empty($name) ? null : (isset($this->data[$name]) ? current($this->data[$name]) : null);
    }

    /**
     * 读取数据
     *
     * @param  string $name
     * @return array
     */
    public function getAttrs($name = '')
    {
        return empty($name) ? $this->data : (isset($this->data[$name]) ? $this->data[$name] : null);
    }
}
