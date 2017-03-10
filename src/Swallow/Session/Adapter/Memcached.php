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
class Memcached extends \Phalcon\Session\Adapter\Libmemcached
{
    use \Swallow\Traits\Session;
}
