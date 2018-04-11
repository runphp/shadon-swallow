<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */

namespace Swallow\Session;

/**
 * session处理适配器
 *
 * @author    SpiritTeam
 * @since     2015年8月12日
 * @version   1.0
 */
abstract class Adapter extends \Phalcon\Session\Adapter
{
    /**
     * 存储引擎
     *
     * @var object
     */
    protected $storage;

    /**
     *
     * @param mixed $options
     *
     * @author    SpiritTeam
     * @since     2015年8月12日
     * @version   1.0
     * 
     */
    public function __construct($options = null)
    {
        !is_array($options) && $options = [];
        !isset($options['name']) && $options['name'] = 'ECM_ID';
        !isset($options['cookiePath']) && $options['cookiePath'] = '/';
        !isset($options['cookieDomain']) && $options['cookieDomain'] = '';
        !isset($options['cookieSecure']) && $options['cookieSecure'] = false;
        !isset($options['prefix']) && $options['prefix'] = 'sess_';
        !isset($options['siteKey']) && $options['siteKey'] = 'eell^&0y<';

        parent::__construct($options);

        /* session_set_save_handler(
            [$this, "open"],
            [$this, "close"],
            [$this, "read"],
            [$this, "write"],
            [$this, "destroy"],
            [$this, "gc"]
        );

        if (!empty($this->_options['sessionId'])) {
            $sessionIdTmp = substr($this->_options['sessionId'], 0, 32);
            if ($this->genSessionKey($sessionIdTmp) == substr($this->_options['sessionId'], 32)) {
                $this->_options['sessionId'] = $sessionIdTmp;
            } else {
                $this->_options['sessionId'] = $this->genSessionId();
                $this->setId($this->_options['sessionId'] . $this->genSessionKey($this->_options['sessionId']));
            }
        } */
    }

    //public function open();

    //public function close();

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
    //public function read($sessionKey);

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
    //public function write($sessionKey, $sessionValue);

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
    //public function destroy($sessionKey);

    /**
     * 垃圾回收session
     *
     * @return boolean
     *
     * @author    SpiritTeam
     * @since     2015年8月12日
     * @version   1.0
     */
    //public function gc();

    /**
     * 生成session验证串
     *
     * @param string $sessionId
     * @return stirng
     *
     * @author    SpiritTeam
     * @since     2015年8月12日
     * @version   1.0
     */
    /* protected function genSessionKey($sessionId)
    {
        return sprintf('%08x', crc32($this->_options['siteKey'] . $sessionId));
    } */

    /**
     * 生成session id
     *
     * @return string
     *
     * @author    SpiritTeam
     * @since     2015年8月12日
     * @version   1.0
     */
    /* protected function genSessionId()
    {
        setcookie(
            "stid",
            time() + rand(100000000, 99999999),
            0,
            $this->_options['cookiePath'],
            $this->_options['cookieDomain'],
            $this->_options['cookieSecure'],
            true
        );
        return md5(uniqid(mt_rand(), true) . '-' . microtime());
    } */

    /**
     * 获取sessionKey
     *
     * @return string
     *
     * @author    SpiritTeam
     * @since     2015年8月12日
     * @version   1.0
     */
    /* protected function getSessionKey()
    {
        return $this->_options['prefix'] . $this->_options['sessionId'];
    } */

}
