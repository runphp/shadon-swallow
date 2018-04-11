<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Traits;

/**
 * 商城用控制器适配器
 *    适配到应用下的控制器
 *
 * @author     SpiritTeam
 * @since      2015年1月22日
 * @version    1.0
 */
trait ControllerAdapter
{

    /**
     * 控制器对象
     * @var \Swallow\Base\Controller;
     */
    private $controller = null;

    /**
     * 执行action
     *
     * @param string $action
     */
    public function do_action($action)
    {
        $moduleAction = $this->convertAction($action);
        if ($action && $action{0} != '_' && (method_exists($this, $action) || method_exists($this->controller, $moduleAction))) {
            $this->_curr_action = $action;
            $this->_run_action();
        } else {
            exit('missing_action');
        }
    }

    /**
     * 调用方法
     *
     * @param string $name
     * @param array  $args
     */
    public function __call($name, $args)
    {
        $name = $this->convertAction($name);
        $this->controller->$name();
    }

    /**
     * 转换action名
     * 
     * @param  string $action
     * @return string
     */
    private function convertAction($action)
    {
        return ucfirst(
            preg_replace_callback('/_([a-zA-Z])/', function ($match) {
                return strtoupper($match[1]);
            }, $action));
    }
}
