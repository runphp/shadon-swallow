<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */

namespace Swallow\Session\Adapter;

/**
 * 使用当前缓存服务保存session.
 *
 * @author    何辉<hehui@eely.net>
 * @since     2015年8月28日
 * @version   1.0
 */
class Cache
    extends \Phalcon\Session\Adapter
    implements \Phalcon\Di\InjectionAwareInterface, \Phalcon\Session\AdapterInterface
{
    use \Swallow\Traits\Session;

    protected $dependencyInjector;

    protected $storage;

    protected $lifeTime;

    /**
     * Sets the dependency injector
     *
     * @param mixed $dependencyInjector
     */
    public function setDI(\Phalcon\DiInterface $dependencyInjector)
    {
        $this->dependencyInjector = $dependencyInjector;
        $this->storage = $dependencyInjector->getCache();
        session_set_save_handler(
            [$this, "open"],
            [$this, "close"],
            [$this, "read"],
            [$this, "write"],
            [$this, "destroy"],
            [$this, "gc"]
        );
    }

    /**
     * Returns the internal dependency injector
     *
     * @return \Phalcon\DiInterface
    */
    public function getDI()
    {
        return $this->dependencyInjector;
    }

    public function open()
    {
        return true;
    }

    public function close()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $sessionId
     * @return mixed
     */
    public function read($sessionId)
    {
        return $this->storage->get($sessionId, $this->lifetime);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $sessionId
     * @param string $data
     */
    public function write($sessionId, $data)
    {
        $this->storage->save($sessionId, $data, $this->lifetime);
    }

    /**
     * {@inheritdoc}
     *
     * @param  string  $sessionId
     * @return boolean
     */
    public function destroy($sessionId = null)
    {
        if ($sessionId === null) {
            $sessionId = $this->getId();
        }
        return $this->storage->delete($sessionId);
    }

    /**
     * {@inheritdoc}
     */
    public function gc()
    {
        return true;
    }
}