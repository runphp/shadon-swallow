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

namespace Swallow\Cache\Backend;

/**
 * 文件缓存.
 *
 * @author     SpiritTeam
 *
 * @since      2015年8月13日
 *
 * @version    1.0
 */
class File extends \Phalcon\Cache\Backend\File implements \Phalcon\DI\InjectionAwareInterface
{
    /**
     * @var \Phalcon\DiInterface
     */
    private $di = null;

    /**
     * Sets the dependency injector.
     *
     * @param mixed $dependencyInjector
     */
    public function setDI(\Phalcon\DiInterface $dependencyInjector): void
    {
        $this->di = $dependencyInjector;
    }

    /**
     * Returns the internal dependency injector.
     *
     * @return \Phalcon\DiInterface
     */
    public function getDI()
    {
        return $this->di;
    }

    /**
     * Returns a cached content.
     *
     * @param int|string $keyName
     * @param long       $lifetime
     *
     * @return mixed
     */
    public function get($keyName, $lifetime = null)
    {
        $application = $this->di->getApplication();
        $appType = $application::APP_TYPE;
        if ('console' != $appType) {
            $clearCache = $this->getDI()->getClearCache()->forceClearCache();
            if (true === $clearCache) {
                return false;
            }
        }

        return parent::get($keyName, $lifetime);
    }
}
