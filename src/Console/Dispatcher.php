<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Console;

class Dispatcher extends \Phalcon\Cli\Dispatcher
{
    public function dispatch()
    {
        $evManager = $this->getDI()->getShared('eventsManager');
        $evManager->attach("dispatch:beforeException",
            function ($event, $dispatcher, $exception) {
                switch ($exception->getCode()) {
                    case self::EXCEPTION_HANDLER_NOT_FOUND:
                    case self::EXCEPTION_ACTION_NOT_FOUND:
                        echo '错误,命令行格式为：php cli.php module_name task_name action_name'."\n";
                        return false;
                }
            });
       $this->setEventsManager($evManager);
       return parent::dispatch();
    }
}