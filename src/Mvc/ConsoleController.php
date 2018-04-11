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

/**
 * 控制器基类
 *
 * @author     SpiritTeam
 * @since      2015年8月13日
 * @version    1.0
 */
abstract class ConsoleController extends \Swallow\Di\Injectable implements \Phalcon\Mvc\ControllerInterface
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
}
