<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Traits;

use Swallow\FastDFS\FastDFS;

/**
 * session Trait
 *
 * @author     SpiritTeam
 * @since      2015年1月13日
 * @version    1.0
 *
 */
trait FastDfsObj
{

    protected $config = [];
    
    /**
     * 获取 FastDFS 对象
     *
     * @return object
     */
    public function getFastDFS()
    {
        $this->config = $this->_dependencyInjector->getConfig()->dfs->toArray();
        static $objDfs = null;
        if (is_null($objDfs)) {
            if (is_array($this->config) && isset($this->config['status']) && true == $this->config['status']) {
                $objDfs = new FastDFS();
                $objDfs->connect_server($this->config['trackerIp'], $this->config['trackerPort']);
            }
        }
        return $objDfs;
    }
}
