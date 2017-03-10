<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Unit;

/**
 * 单元测试
 *
 * @author     SpiritTeam
 * @since      2015年8月13日
 * @version    1.0
 */
class UnitTestCase extends \PHPUnit_Framework_TestCase
{

    /**
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    public function setUp()
    {
        $class = explode('\\', get_class($this));
        $di = \Phalcon\DI::getDefault();
        $module = $class[0] . '\Module';
        $moduleObject = $di->get($module);
        $moduleObject->registerAutoloaders($di);
        $moduleObject->registerServices($di);
    }
}
