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
class Memcache extends \Phalcon\Session\Adapter\Memcache
{
    use \Swallow\Traits\Session;
    
    /**
     * Phalcon\Session\Adapter\Memcache constructor
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        $servers = $options['servers'];
        $randKey = array_rand($servers, 1);
        $options = array_merge($options, $servers[$randKey]);
        parent::__construct($options);
    }
}
