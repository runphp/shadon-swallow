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
 * 服务初始化Trait
 *
 * @author     SpiritTeam
 * @since      2015年1月13日
 * @version    1.0
 *
 */
trait InitService
{
    /**
     * 初始化
     *
     * @param mixed $options
     *
     * @author    SpiritTeam
     * @since     2015年8月12日
     * @version   1.0
     */
    public function init($options = null)
    {
        if (is_array($options)) {
            foreach ($options as $optionKey => $optionValue) {
                $method = 'set' . ucfirst($optionKey);
                if (method_exists($this, $method)) {
                    $this->$method($optionValue);
                }
            }
        }
    }

    /**
     * 执行。预留方法
     *
     * @param mixed $options
     *
     * @author    SpiritTeam
     * @since     2015年8月12日
     * @version   1.0
     */
    public function run()
    {
        
    }

}
