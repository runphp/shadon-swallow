<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Plugin;

use Swallow\Traits\OutputProfilers;

/**
 * 监听器
 * 
 * @author    范世军<fanshijun@eelly.net>
 * @since     2015年9月16日
 * @version   1.0
 */
class HandleTask extends \Swallow\Di\Injectable
{
    
    use OutputProfilers;

    /**
     * 如果事件触发器是'beforeHandleTask'，此函数将会被执行
     */
    public function beforeHandleTask($event, $application)
    {
    }

    /**
     * 如果事件触发器是'afterHandleTask'，此函数将会被执行
     */
    public function afterHandleTask($event, $application)
    {
        $this->OutputProfilers();
    }
}