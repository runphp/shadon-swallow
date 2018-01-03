<?php
/*
 * PHP version 5.5
 *
 * @copyright Copyright (c) 2012-2017 EELLY Inc. (http://www.eelly.com)
 * @link      http://www.eelly.com
 * @license   衣联网版权所有
 */

namespace Swallow\Di;

use Phalcon\Di\Service;
use Swallow\Base\Logic;
use Swallow\Core\Conf;
use Swallow\Events\Event\CacheListener;
use Swallow\Events\Event\DeprecatedListener;
use Swallow\Events\Manager as EventsManager;
use Swallow\Mvc\Collection\Manager as CollectionManager;

/**
 * @author hehui<hehui@eelly.net>
 *
 * @since 2016年10月3日
 *
 * @version 1.0
 */
class FactoryDefault extends \Phalcon\Di\FactoryDefault
{
    /**
     * @author hehui<hehui@eelly.net>
     *
     * @since 2017年4月6日
     */
    public function __construct()
    {
        parent::__construct();
        // 请按字母顺序排列.
        $this->_services['annotionsReader'] = new Service('annotionsReader', function () {
            $config = Conf::get('annotations');
            $adapter = $this->get($config['adapter'], [$config['options'][$config['adapter']]]);

            return $adapter;
        }, true);
        $this->_services['collectionManager'] = new Service('collectionManager', CollectionManager::class, true);
        $this->_services['eventsManager'] = new Service('eventsManager', function () {
            $eventsManager = new EventsManager();
            $eventsManager->enablePriorities(true);
            $eventsManager->attach(Logic::class, $this->getShared(CacheListener::class), 50);
            MODULE_DEBUG && $eventsManager->attach(Logic::class, $this->getShared(DeprecatedListener::class), 100);

            return $eventsManager;
        }, true);
    }
}
