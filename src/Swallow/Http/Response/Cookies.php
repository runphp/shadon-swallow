<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */

namespace Swallow\Http\Response;

/**
 * cookies
 *
 * @author     SpiritTeam
 * @since      2015年8月13日
 * @version    1.0
 */
class Cookies extends \Phalcon\Http\Response\Cookies
{
    /**
     * 添加了读取配置的cookieDomain
     * 过期时间追加当前时间
     *
     * (non-PHPdoc)
     * @see \Phalcon\Http\Response\Cookies::set()
     */
    public function set($name, $value = null, $expire = 0, $path = "/", $secure = null, $domain = null, $httpOnly = null)
    {
        $expire += time();
        if (! $domain) {
            try {
                $domain = $this->getDI()->getConfig()->cookieDomain;
            } catch (\Exception $e) {
                $domain = '.eelly.com';
            }
        }
        parent::set($name, $value, $expire, $path, $secure, $domain, $httpOnly);
    }
    
    /**
     * 获取
     * 
     * (non-PHPdoc)
     * @see \Phalcon\Http\Response\Cookies::get()
     */
    public function get($name)
    {
        return parent::get($name)->getValue();
    }
}
