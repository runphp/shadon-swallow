<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Rpc;

use Swallow\Rpc\Rpc;
use Swallow\Swallow;

/**
 * Soa方式远程设用
 * 
 * @author    SpiritTeam
 * @since     2015年6月3日
 * @version   1.0
 */
class Soa implements Rpc
{

    /**
     * 配置
     * @var array
     */
    protected $conf = array();

    /**
     * 构造
     */
    public function __construct($conf)
    {
        $this->conf = $conf;
    }

    /**
     * 调用Rpc
     *
     * @param  string $class
     * @param  string $method
     * @param  array $agrs
     * @return mixed
     */
    public function call($class, $method, $agrs)
    {
        $client = $this->getClient();
        //echo $class, ' > ', $method, ' > ', json_encode($agrs), "\n";
        $r = $client->call($class . '::' . $method, $agrs);
        return $r;
    }

    /**
     * 获取客户端实例 
     * 
     * @return \Soa\Client
     */
    public function getClient()
    {
        static $client = null;
        if (isset($client)) {
            return $client;
        }
        //include ROOT_PATH . '/../eelly-core/Soa/Soa.php';
        include __DIR__ . '/ESoa.phar';
        $client = \Soa\Client::getInstance();
        $client->setProtocol('Soa');
        list ($host, $port) = explode(':', $this->conf['manage'], 2);
        $client->setManage(array('host' => $host, 'port' => $port));
        return $client;
    }
}