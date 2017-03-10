<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Bootstrap;

/**
 * 启动器接口.
 *
 * @author    何辉<hehui@eely.net>
 * @since     2015年8月25日
 * @version   1.0
 */
interface BootstrapInterface extends \Phalcon\Di\InjectionAwareInterface
{
    public function bootStrap();
}