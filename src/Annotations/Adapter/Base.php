<?php
/*
 * PHP version 5.5
 *
 * @copyright Copyright (c) 2012-2017 EELLY Inc. (http://www.eelly.com)
 * @link      http://www.eelly.com
 * @license   衣联网版权所有
 */

namespace Swallow\Annotations\Adapter;

use Phalcon\Annotations\Adapter;

/**
 * Base class for annotations adapters.
 *
 * @author    hehui<hehui@eelly.net>
 *
 * @since     2017年5月18日
 *
 * @version   1.0
 */
abstract class Base extends Adapter
{
    /**
     * Default option for cache lifetime.
     *
     * @var array
     */
    protected static $defaultLifetime = 8600;

    /**
     * Default option for prefix.
     *
     * @var string
     */
    protected static $defaultPrefix = '_D_';

    /**
     * Backend's options.
     *
     * @var array
     */
    protected $options = null;

    /**
     * Class constructor.
     *
     * @param null|array $options
     *
     * @throws \Phalcon\Mvc\Model\Exception
     */
    public function __construct($options = null)
    {
        if (!is_array($options) || !isset($options['lifetime'])) {
            $options['lifetime'] = self::$defaultLifetime;
        }
        if (!is_array($options) || !isset($options['prefix'])) {
            $options['prefix'] = self::$defaultPrefix;
        }
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $key
     *
     * @return array
     */
    public function read($key)
    {
        return $this->getCacheBackend()->get($this->prepareKey($key), $this->options['lifetime']);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $key
     * @param array  $data
     */
    public function write($key, $data)
    {
        $this->getCacheBackend()->save($this->prepareKey($key), $data, $this->options['lifetime']);
    }

    /**
     * Returns the key with a prefix or other changes.
     *
     * @param string $key
     *
     * @return string
     */
    abstract protected function prepareKey($key);

    /**
     * Returns cache backend instance.
     *
     * @return \Phalcon\Cache\BackendInterface
     */
    abstract protected function getCacheBackend();
}
