<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */

namespace Swallow\Db;

use Phalcon\Di\InjectionAwareInterface;

/**
 * 数据库基类
 *
 * @author     SpiritTeam
 * @since      2015年8月13日
 * @version    1.0
 */
class Mysql extends \Phalcon\Db\Adapter\Pdo\Mysql implements InjectionAwareInterface
{
    protected $di;
    
    /**
     * Sets the dependency injector
     *
     * @param mixed $di
     */
    public function setDI(\Phalcon\DiInterface $di)
    {
        $this->di = $di;
        $this->setEventsManager($di->getEventsManager());
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
}
