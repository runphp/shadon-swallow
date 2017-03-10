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

/**
 *
 * @author     SpiritTeam
 * @since      2015年8月13日
 * @version    1.0
 */
class WebDi extends \Phalcon\Di\FactoryDefault
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
            'application'        => new Service('application', "\Swallow\Mvc\Application", true),
            'assets'             => new Service('assets', "\Swallow\Assets\Manager", true),
            "dispatcher"         => new Service("dispatcher", "\Swallow\Mvc\Dispatcher", true),
            'escaper'            => new Service('escaper', "\Swallow\Escaper", true),
            'eventsManager'      => new Service('eventsManager', "\Swallow\Events\Manager", true),
            'filter'             => new Service('filter', "\Swallow\Filter", true),
            'loader'             => new Service('loader', "\Swallow\Loader\Loader", true),
            'logger'             => new Service('logger', "\Swallow\Logger\Logger", true),
            'modelsManager'      => new Service('modelsManager', "\Swallow\Mvc\Model\Manager", true),
            'modelsMetadata'     => new Service('modelsMetadata', "\Swallow\Mvc\Model\MetaData\Files", true),
            'php'                => new Service('php', "\Swallow\Mvc\View\Engine\Php", true),
            'request'            => new Service('request', "\Swallow\Http\Request", true),
            'response'           => new Service('response', "\Swallow\Http\Response", true),
            'router'             => new Service('router', "\Swallow\Mvc\Router", true),
            'security'           => new Service('security', "\Swallow\Security", true),
            'sessionBag'         => new Service('sessionBag', "\Swallow\Session\Bag", true),
            'tag'                => new Service('tag', "\Swallow\Tag", true),
            'transactionManager' => new Service('transactionManager', "\Swallow\Mvc\Model\Transaction\Manager", true),
            'url'                => new Service('url', "\Swallow\Mvc\Url", true),
            'view'               => new Service('view', "\Swallow\Mvc\View", true),
            'visitor'            => new Service('visitor', "\Swallow\Auth\Visitor", true),
            'clearCache'         => new Service('clearCache', "\Swallow\Debug\ClearCache", true),
            'collectionManager'  => new Service('collectionManager', "\Swallow\Mvc\Collection\Manager", true),
            'defaultCache'       => new Service('cache', "\Swallow\Cache\DefaultCache", true),
        ];
    }
}
