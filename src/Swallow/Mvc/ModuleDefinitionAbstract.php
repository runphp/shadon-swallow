<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */

namespace Swallow\Mvc;

use Phalcon\DiInterface;
use Phalcon\Mvc\ModuleDefinitionInterface;

/**
 * 模块基类
 *
 * @author     SpiritTeam
 * @since      2015年8月13日
 * @version    1.0
 */
abstract class ModuleDefinitionAbstract implements ModuleDefinitionInterface
{
    /**
     * 初始化。需要在registerServices方法里面显式调用$this->init();
     *
     * @param \Phalcon\DiInterface $di
     */
    public function init($di)
    {
        $this->initDispatcher($di);
        $this->initConfig($di);
        $this->initView($di);
    }

    /**
     * 初始化调度器
     *
     * Example
     * <code>
     *  $dispatcher = $di->get('dispatcher');
     *  $dispatcher->setDefaultNamespace("Multiple\Backend\Controllers");
     * </code>
     *
     *  @param \Phalcon\DiInterface $di
     */
    abstract public function initDispatcher(DiInterface $di);

    /**
     * 初始化配置
     *
     *  @param \Phalcon\DiInterface $di
     */
    abstract public function initConfig(DiInterface $di);

    /**
     * 初始化视图
     *
     * Example
     * <code>
     *  $view = $di->get('view');
     *  $view->setViewsDir(__DIR__ . '/views/');
     * </code>
     *
     *  @param \Phalcon\DiInterface $di
     */
    abstract public function initView(DiInterface $di);

}
