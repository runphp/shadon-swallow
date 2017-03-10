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

class ConsoleDi extends \Phalcon\Di\FactoryDefault\Cli
{

    /**
     * 构造方法
     *
     */
    public function __construct()
    {
        parent::__construct();
        // 请按字母顺序排列.
        $this->_services = [
            'annotations'        => new Service('annotations', "\Swallow\Annotations\Adapter\Files", true),
            'application'        => new Service('application', "\Swallow\Console\Application", true),
            "dispatcher"         => new Service("dispatcher", "\Swallow\Console\Dispatcher", true),
            'eventsManager'      => new Service('eventsManager', "\Swallow\Events\Manager", true),
            'loader'             => new Service('loader', "\Swallow\Loader\Loader", true),
            'logger'             => new Service('logger', "\Swallow\Logger\Logger", true),
            'modelsManager'      => new Service('modelsManager', "\Swallow\Mvc\Model\Manager", true),
            'modelsMetadata'     => new Service('modelsMetadata', "\Swallow\Mvc\Model\MetaData\Files", true),
            "router"             => new Service("router", "\Swallow\\Console\Router", true),
            'security'           => new Service('security', "\Swallow\Security", true),
            'transactionManager' => new Service('transactionManager', "\Swallow\Mvc\Model\Transaction\Manager", true),
        ];
    }
}