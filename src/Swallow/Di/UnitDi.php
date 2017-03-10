<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Di;

use Phalcon\Di\Service;

class UnitDi extends \Swallow\Di\WebDi
{

    /**
     * 构造方法
     *
     */
    public function __construct()
    {
        parent::__construct();
        // 请按字母顺序排列.
        $this->_services['application'] = new Service('application', "\Swallow\Unit\Application", true);
    }
}