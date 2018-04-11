<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Events;

class EventsRegister implements \Swallow\Bootstrap\BootstrapInterface
{
    protected $di;
    
    /**
     * Sets the dependency injector
     *
     * @param mixed $dependencyInjector
     */
    public function setDI(\Phalcon\DiInterface $dependencyInjector)
    {
        $this->di = $dependencyInjector;
    }

    /**
     * Returns the internal dependency injector
     *
     * @return \Phalcon\DiInterface
     */
    public function getDI()
    {
        return $this->di;
    }

    public function bootStrap()
    {
        //监听事件
        $eventsManager = $this->di->getEventsManager();
        if(APP_DEBUG){
            $application = $this->di->getApplication();
            $appType = $application::APP_TYPE;
            if($appType == 'console'){
                $eventsManager->attach('console', $this->di->getShared('\Swallow\Plugin\HandleTask'));
            }else {
                $eventsManager->attach('application', $this->di->getShared('\Swallow\Plugin\HandleRequest'));
            }
        }
        $eventsManager->attach('db', $this->di->getShared('\Swallow\Plugin\Query'));
        $eventsManager->attach('application', $this->di->getShared('\Swallow\Plugin\StartModule'));
        $eventsManager->attach('application', $this->di->getShared('\Swallow\Plugin\SendResponse'));
    }
}