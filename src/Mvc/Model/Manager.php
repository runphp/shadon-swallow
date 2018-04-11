<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */

namespace Swallow\Mvc\Model;

/**
 * 数据库模型管理器
 *
 * @author     SpiritTeam
 * @since      2015年8月13日
 * @version    1.0
 */
class Manager extends \Phalcon\Mvc\Model\Manager
{
    public function load($modelName, $newInstance = false)
    {
        //if (! class_exists($modelName, $autoload = null))
        if (strpos($modelName, '\\') === false) {
            $nameSpace = get_class(current($this->_initialized));
            $nameSpace = substr($nameSpace, 0, strrpos($nameSpace, '\\') + 1);
            $modelName = $nameSpace . $modelName;
        }
        return parent::load($modelName, $newInstance);
    }
}
