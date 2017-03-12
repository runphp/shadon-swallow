<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Plugin;

/**
 * 应用启动事件
 * 
 * @author    范世军<fanshijun@eelly.net>
 * @since     2015年9月16日
 * @version   1.0
 */
class SendResponse extends \Swallow\Di\Injectable
{

    /**
     * 事件触发器，此函数将会被执行
     * 
     * @param $event
     * @param $obj
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年9月24日
     */
    public function beforeSendResponse($event, $obj)
    {
        //fis3异步资源加载
        $view = $this->getDI()->getView();
        if (null === $view->getViewsDir() || is_array($view->getViewsDir()) && count($view->getViewsDir()) == 0) {
            return;
        }
        $viewsDir = substr($view->getViewsDir(), 9);
        $pickView = $view->getPickView();
        $pickViewNum = count($pickView);
        switch ($pickViewNum) {
            case 2:
                $id = $viewsDir . $pickView[0];
                break;
            case 1:
                $actionName = $view->getActionName();
                $id = $viewsDir . $pickView[0] . $actionName;
                break;
            default:
                $controllerName = $view->getControllerName();
                $actionName = $view->getActionName();
                $id = $viewsDir . $controllerName . '/' . $actionName;
        }
        $id = $id . '.phtml';
        
        $resource = $this->getDI()->getResource();
        $resource->getPhtmlLoad($id);
        $response = $this->getDI()->getResponse();
        $content = $response->getContent();
        $html = $resource->renderHtml($content);
        $response->setContent($html);
    }
}