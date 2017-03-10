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
 * mvc 分发器.
 *
 * @author    SpiritTeam
 * @since     2015年8月12日
 * @version   1.0
 */
class Dispatcher extends \Phalcon\Mvc\Dispatcher
{
    public function dispatch()
    {
        $evManager = $this->getDI()->getShared('eventsManager');
        $evManager->attach("dispatch:beforeException",
            function ($event, $dispatcher, $exception) {
                switch ($exception->getCode()) {
                    case self::EXCEPTION_HANDLER_NOT_FOUND:
                    case self::EXCEPTION_ACTION_NOT_FOUND:
                        $dispatcher->setModuleName('common');
                        $dispatcher->forward(['namespace'=>'Common\Controller', 'controller' => 'error', 'action' => 'notFound']);
                        return false;
                }
            });
        $this->setEventsManager($evManager);
        return parent::dispatch();
    }
}
