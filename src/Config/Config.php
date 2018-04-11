<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */

namespace Swallow\Config;

/**
 * 配置类
 *
 * @author     SpiritTeam
 * @since      2015年8月13日
 * @version    1.0
 */
class Config extends \Phalcon\Config
{
    public function mergeArray(array $config)
    {
        return $this->merge(new Config($config));
    }
}
