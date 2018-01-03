<?php

/*
 * 
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Hook;

/**
 * 行为基类
 * 
 * @author    zengzhihao<zengzhihao@eelly.net>
 * @since     2016年3月10日
 * @version   1.0
 */
class Behavior
{

    /**
     * 构造器
     */
    final protected function __construct()
    {
        $this->init();
    }

    /**
     * 初始化
     */
    protected function init()
    {
    }

    /**
     * 获取单例
     *
     * @return self
     */
    public static function getInstance()
    {
        static $class = [];
        $called = get_called_class();
        if (! isset($class[$called])) {
            $class[$called] = new $called();
        }
        
        return $class[$called];
    }
}