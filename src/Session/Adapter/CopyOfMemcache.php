<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */

namespace Swallow\Session\Adapter;

/**
 * session-memcache
 *
 * @author    SpiritTeam
 * @since     2015年8月12日
 * @version   1.0
 */
class Memcache extends \Swallow\Session\Adapter implements \Phalcon\Session\AdapterInterface
{
    /**
     *
     * @param type $options
     *
     * @author    SpiritTeam
     * @since     2015年8月12日
     * @version   1.0
     */
    public function __construct($options = null)
    {
        $cacheFrontend = new $options->frontend->class($options->frontend->options);
        $cacheBackend = new $options->backend->class($cacheFrontend, $options->backend->options);
        $this->storage = $cacheBackend;
        
        unset($options['frontend']);
        unset($options['backend']);

        parent::__construct($options);
    }

    /**
     * 打开session
     *
     * @author    SpiritTeam
     * @since     2015年8月12日
     * @version   1.0
     */
    public function open()
    {
        return true;
    }

    /**
     * 关闭session
     *
     * @author    SpiritTeam
     * @since     2015年8月12日
     * @version   1.0
     */
    public function close()
    {
        return true;
    }

    /**
     * 读session
     *
     * @param string $sessionKey
     * @return string
     *
     * @author    SpiritTeam
     * @since     2015年8月12日
     * @version   1.0
     */
    public function read($sessionKey)
    {
        return $this->storage->get($this->getSessionKey());
    }

    /**
     * 写session
     *
     * @param string $sessionKey
     * @param mixed $sessionValue
     *
     * @return boolean
     *
     * @author    SpiritTeam
     * @since     2015年8月12日
     * @version   1.0
     */
    public function write($sessionKey, $sessionValue)
    {
        return $this->storage->save($this->getSessionKey(), $sessionValue);
    }

    /**
     * 销毁session
     *
     * @param string $sessionKey
     *
     * @return boolean
     *
     * @author    SpiritTeam
     * @since     2015年8月12日
     * @version   1.0
     */
    public function destroy($sessionKey)
    {
        $_SESSION = array();
        setcookie(
            $this->_options['name'],
            $this->_options['sessionId'],
            1,
            $this->_options['cookiePath'],
            $this->_options['cookieDomain'],
            $this->_options['cookieSecure']
        );
        return $this->storage->delete($this->getSessionKey());
    }

    /**
     * 垃圾回收session
     *
     * @return boolean
     *
     * @author    SpiritTeam
     * @since     2015年8月12日
     * @version   1.0
     */
    public function gc()
    {
        return true;
    }
}
