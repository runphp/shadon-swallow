<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Traits;

/**
 * session Trait
 *
 * @author     SpiritTeam
 * @since      2015年1月13日
 * @version    1.0
 *
 */
trait Session
{

    /**
     * @var session id
     */
    private $sessionId;

    /**
     * @var options
     */
    private $options;

    /**
     * Starts the session (if headers are already sent the session will not be started)
     *
     * @return bool 
     */
    public function start()
    {
        $options = $this->getOptions();
        ! is_array($options) && $options = [];
        ! isset($options['name']) && $options['name'] = 'EELLYSESSID';
        ! isset($options['cookiePath']) && $options['cookiePath'] = '/';
        ! isset($options['cookieDomain']) && $options['cookieDomain'] = '';
        ! isset($options['cookieSecure']) && $options['cookieSecure'] = false;
        ! isset($options['cookieHttponly']) && $options['cookieHttponly'] = false;
        ! isset($options['siteKey']) && $options['siteKey'] = 'eell^&0y<';
        $this->options = $options;
        
        $this->setName($options['name']);
        $this->sessionInt();
        session_set_cookie_params(0, $options['cookiePath'], $options['cookieDomain'], $options['cookieSecure'], $options['cookieHttponly']);
        return parent::start();
    }

    /**
     * Set session id
     */
    private function sessionInt()
    {
        /*处理session id*/
        $name = $this->options['name'];
        $cookieSession = isset($_COOKIE[$name]) ? $_COOKIE[$name] : '';
        $this->sessionId = ! empty($cookieSession) ? $cookieSession : '';
        if ($this->sessionId) {
            $tmpSessionId = substr($this->sessionId, 0, 32);
            $sessionKey = $this->genSessionKey($tmpSessionId);
            $this->sessionId = $sessionKey == substr($this->sessionId, 32) ? $tmpSessionId : '';
        }
        
        if (empty($this->sessionId)) {
            $this->genSessionId();
            $this->setId($this->sessionId . $this->genSessionKey($this->sessionId));
        } elseif ($this->sessionId != $cookieSession) {
            $this->setId($this->sessionId . $this->genSessionKey($this->sessionId));
        }
    }

    /**
     * Set session id
     */
    public function setId($sessionId)
    {
        return parent::setId($sessionId);
    }

    /**
     * 生成session id
     *
     * @return string
     */
    private function genSessionId()
    {
        $this->sessionId = md5(uniqid(mt_rand(), true) . '-' . microtime());
    }

    /**
     * 生成session验证串
     *
     * @param string $session_id
     * @return stirng
     */
    private function genSessionKey($sessionId)
    {
        return sprintf('%08x', crc32($this->options['siteKey'] . $sessionId));
    }
}
