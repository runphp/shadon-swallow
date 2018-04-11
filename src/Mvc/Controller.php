<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Mvc;

use Swallow\Traits\PublicObject;
use Swallow\Toolkit\Net\Curl;
use Swallow\Exception\StatusCode;

/**
 * 控制器基类
 *
 * @author     SpiritTeam
 * @since      2015年8月13日
 * @version    1.0
 */
abstract class Controller extends \Swallow\Di\Injectable implements \Phalcon\Mvc\ControllerInterface
{
    
    use PublicObject;

    /**
     * 构造
     * 
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年9月23日
     */
    public final function __construct()
    {
        if (method_exists($this, "onConstruct")) {
            $this->onConstruct();
        }
    }

    /**
     * json输出
     *
     * @param int $status 状态
     * @param string $info 返回信息
     * @param array $retval 返回值
     * @author zengzhihao<zengzhihao@eelly.net>
     * @since  2015年10月14日
     */
    public function jsonEcho($status, $info, $retval = [])
    {
        $json = json_encode(array('status' => $status, 'info' => $info, 'retval' => $retval));
        //存在$_GET['callback']，代表是跨域请求
        /* if (! empty($_GET['callback']) || ! empty($_POST['callback'])) {
         $prex = ! empty($_GET['callback']) ? $_GET['callback'] : $_POST['callback'];
         $json = $prex . '(' . $json . ')';
         } */
        $this->view->disable();
        echo $json;
    }

    /**
     * 成功
     *
     * @param string $msg 信息
     * @param string $urlTo 跳转到的页面链接
     * @author zengzhihao<zengzhihao@eelly.net>
     * @since  2015年10月15日
     */
    public function success($msg = '', $urlTo = '')
    {
        $msg = $msg ? $msg : 'SUCCESS!';
        $this->showMessage($msg, $urlTo);
    }

    /**
     * 错误
     *
     * @param string $msg 信息
     * @param string $urlTo 跳转到的页面链接
     * @author zengzhihao<zengzhihao@eelly.net>
     * @since  2015年10月15日
     */
    public function error($msg = '', $urlTo = '')
    {
        $msg = $msg ? $msg : 'ERROR!';
        $this->showMessage($msg, $urlTo);
    }

    /**
     * 跳转到提示信息页
     *
     * @param string $msg 信息
     * @param string $urlTo 跳转到的页面链接
     * @author zengzhihao<zengzhihao@eelly.net>
     * @since  2015年10月15日
     */
    public function showMessage($msg = 'Not Message!', $urlTo = '')
    {
        $controllerName = ucfirst($this->dispatcher->getModuleName());
        if ($controllerName == 'Common') {
            $this->tag->setTitle("提示");
            $this->view->msg = $msg;
            $this->view->waitSecond = 2;
            $this->view->urlTo = $urlTo;
            $value = $this->view->getPartial('message/index');
        } else {
            $curl = new Curl();
            $url = $this->di->getConfig()->url->site;
            $data = $curl->post($url . '/common/message', ['msg' => $msg, 'urlTo' => $urlTo]);
            $value = $data['status'] == StatusCode::OK ? $data['body'] : '';
        }
        $this->view->disable();
        echo $value;
    }

    /**
     * 前端调试方法
     *
     * @author zengzhihao<zengzhihao@eelly.net>
     * @since  2015年11月2日
     */
    public final function frontDebugAction()
    {
        $view = $this->dispatcher->getParam("view");
        if (empty($view) || ! APP_DEBUG)
            return false;
        
        $this->view->pick($view);
    }
}
