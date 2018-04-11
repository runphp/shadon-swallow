<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Mvc;

/**
 * 控制器基类
 *
 * @author     SpiritTeam
 * @since      2015年8月13日
 * @version    1.0
 */
abstract class QueueConsoleController extends ConsoleController
{
    /**
     * 判断当前是否应该退出队列循环
     * 
     * @return boolean
     * @author 范世军<fanshijun@eelly.net>
     * @since  Dec 31, 2015
     */
    protected function isTimeToExit()
    {
        $hour = date('Hi');
        if ($hour >= '0400' && $hour <= '0404') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 立即退出队列循环
     *
     * @return boolean
     * @author 范世军<fanshijun@eelly.net>
     * @since  Dec 31, 2015
     */
    protected function nowExit()
    {
        //由于在db实例化之前，会先检测主从延迟问题，采取这种方法可避免，数据已经从队列取出来了，但到了实例化db的时候，程序退出而导致的问题
        //db();
        $cacheKey = 'queue_' . CURRENT_MODULE . '_' . CURRENT_TASK . '_' . CURRENT_ACTION;
        //$cachePrefix = 'threeMinutes';
        $defaultDi = $this->getDI();
        $cacheServer = $defaultDi['cacheManager']->getServer(['type' => 'statisticst']);
        $cacheValue = $cacheServer->get($cacheKey);
        if ('1' == $cacheValue) {
            $cacheServer->save($cacheKey, '0', 180);
            $cacheServer->delete($cacheKey);
            echo date('y-m-d H:i:s') . " 手动退出队列循环\n";
            return true;
        } else {
            return false;
        }
    }

    /**
     * 手动退出队列循环进程
     * 结合ConsoleController的nowExit方法使用
     *
     * @param  $params
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年10月16日
     */
    public function quitQueueTaskAction()
    {
        $params = func_get_args();
        $params = ! empty($params[0]) ? $params[0] : [];
        if (empty($params[0]) || empty($params[1]) || empty($params[2])) {
            echo "缺少必要的参数\n";
            return false;
        }
        $defaultDi = $this->getDI();
        $cacheKey = 'queue_' . $params[0] . '_' . $params[1] . '_' . $params[2];
        //$cachePrefix = 'threeMinutes';
        $cacheServer = $defaultDi['cacheManager']->getServer(['type' => 'statisticst']);
        $cacheServer->save($cacheKey, '1', 180); //三分钟
        //$var = $cacheServer->get($cacheKey);
        //dd($var);
        //$this->queueEnd(json_encode(array(1 => $params[1], 2 => $params[2], 3 => $params[3])), false);
        return true;
    }
}
