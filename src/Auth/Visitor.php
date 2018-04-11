<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Auth;

/**
 * 用戶登陆信息
 *
 * @author     SpiritTeam
 * @since      2015年8月13日
 * @version    1.0
 */
class Visitor implements \Phalcon\DI\InjectionAwareInterface
{

    /**
     * @var \Phalcon\DiInterface
     */
    private $di = null;

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
    
    /**
     * 获取登录用户信息
     * 
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年9月23日
     */
    public function getUserInfo()
    {
        $session = $this->getDI()->getSession();
        $userInfo = $session->get('userInfo');
        return $userInfo;
    }
    
    /**
     * 判断是否登陆
     *
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年9月23日
     */
    public function islogin()
    {
        $session = $this->getDI()->getSession();
        $islogin = $session->get('islogin');
        return $islogin ? true : false ;
    }
}
