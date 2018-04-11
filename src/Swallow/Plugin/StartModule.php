<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Plugin;

/**
 * 应用启动事件
 * 
 * @author    范世军<fanshijun@eelly.net>
 * @since     2015年9月16日
 * @version   1.0
 */
class StartModule extends \Swallow\Di\Injectable
{

    /**
     * 事件触发器，此函数将会被执行
     * 
     * @param $event
     * @param $obj
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年9月24日
     */
    public function afterStartModule($event, $obj)
    {
        $this->getOldSessionId();
    }

    /**
     * 获取旧版框架SessionId
     * 
     * @param string $name
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年10月17日
     */
    public function getOldSessionId($name = 'ECM_ID')
    {
        $cookieSession = isset($_COOKIE[$name]) ? $_COOKIE[$name] : '';
        if (! empty($cookieSession)) {
            $defaultDi = $this->getDI();
            $cache = $defaultDi->getCache();
            $tmpSessionId = substr($cookieSession, 0, 32);
            $userInfo = $cache->get('sess_' . $tmpSessionId);
            $userInfo = $this->unserializePhp($userInfo);
            $session = $defaultDi->getSession();
            if (! empty($userInfo['islogin']) && isset($userInfo['user_info'])) {
                ! $session->has('islogin') && $session->set('islogin', $userInfo['islogin']);
                ! $session->has('userInfo') && $session->set('userInfo', $userInfo['user_info']);
            } else {
                $session->has('islogin') && $session->remove('islogin');
                $session->has('userInfo') && $session->remove('userInfo');
            }
        }
    }

    /**
     * 反序列化session 在PHP官方网站可以找到
     * 
     * @param $session_data
     * @throws Exception
     * @return multitype:mixed 
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年9月24日
     */
    private function unserializePhp($session_data)
    {
        $return_data = array();
        $offset = 0;
        while ($offset < strlen($session_data)) {
            if (! strstr(substr($session_data, $offset), "|")) {
                throw new \Exception("invalid data, remaining: " . substr($session_data, $offset));
            }
            $pos = strpos($session_data, "|", $offset);
            $num = $pos - $offset;
            $varname = substr($session_data, $offset, $num);
            $offset += $num + 1;
            $data = unserialize(substr($session_data, $offset));
            $return_data[$varname] = $data;
            $offset += strlen(serialize($data));
        }
        return $return_data;
    }
}