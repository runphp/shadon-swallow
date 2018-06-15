<?php

declare(strict_types=1);

/*
 * This file is part of eelly package.
 *
 * (c) eelly.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swallow\Di;

use Phalcon\Di\Service;
use Swallow\Base\Logic;
use Swallow\Core\Conf;
use Swallow\Events\Event\CacheListener;
use Swallow\Events\Event\CatchListener;
use Swallow\Events\Event\DeprecatedListener;
use Swallow\Events\Event\TransactionListener;
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
            // logic listener
            $eventsManager->attach(Logic::class, $this->getShared(TransactionListener::class), 50);
            $eventsManager->attach(Logic::class, $this->getShared(CacheListener::class), 100);
            MODULE_DEBUG && $eventsManager->attach(Logic::class, $this->getShared(DeprecatedListener::class), 150);
            // service listener
            $eventsManager->attach(\Swallow\Base\Service::class, $this->getShared(CatchListener::class), 100);

            return $eventsManager;
        }, true);
    }
}
