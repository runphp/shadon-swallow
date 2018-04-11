<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Base;

use Swallow\Core\Base;

/**
 * 逻辑业务基类
 * 逻辑业务的编写
 * 
 * @author     SpiritTeam
 * @since      2015年1月12日
 * @version    1.0
 */
abstract class Logic extends Base
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
}
