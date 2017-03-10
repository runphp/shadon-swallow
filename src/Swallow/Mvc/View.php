<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Mvc;

use Phalcon\Mvc\View\Engine\Volt as VoltEngine;

/**
 * 模块基类
 *
 * @author     SpiritTeam
 * @since      2015年8月13日
 * @version    1.0
 */
class View extends \Phalcon\Mvc\View
{

    /**
     *
     * @param string $options
     * @return \Phalcon\Mvc\View\Engine\Volt|\Swallow\Mvc\View\Engine\Smarty
     * @author 何辉<hehui@eely.net>
     * @since  2015年9月2日
     */
    public function __construct($options = null)
    {
        parent::__construct($options);
        $this->registerEngines(['.phtml' => 'Swallow\Mvc\View\Engine\Php']);
    }

    /**
     * (non-PHPdoc).
     * @see \Phalcon\Mvc\View::_engineRender()
     */
    protected function _engineRender($engines, $viewPath, $silence, $mustClean, \Phalcon\Cache\BackendInterface $cache = null)
    {
        if (null === $this->getViewsDir()) {
            $moduleName = $this->getDI()
                ->getDispatcher()
                ->getModuleName();
            //$this->setViewsDir("application/$moduleName/view/");
            $this->setViewsDir("resource/templates/$moduleName/");
        }
        return parent::_engineRender($engines, $viewPath, $silence, $mustClean, $cache);
    }

    /**
     * (non-PHPdoc)
     * @see \Phalcon\Mvc\View::__get()
     */
    public function __get($key)
    {
        return parent::__get($key);
    }

    /**
     * 获取赋值参数
     * 
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年10月12日
     */
    public function getViewParams()
    {
        return $this->_viewParams;
    }
    
    /**
     * _pickView
     *
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年10月12日
     */
    public function getPickView()
    {
        return $this->_pickView;
    }
}
