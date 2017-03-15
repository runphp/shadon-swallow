<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Cache;

/**
 * 缓存管理器
 *
 * @author    SpiritTeam
 * @since     2015年3月10日
 * @version   1.0
 */
class CacheManager implements \Phalcon\DI\InjectionAwareInterface
{

    /**
     * var string
     */
    private $type = 'default';

    /**
     * @var \Phalcon\DiInterface
     */
    private $di = null;

    /**
     * Sets the dependency injector
     *
     * @param mixed $dependencyInjector
     */
    public function setDI(\Phalcon\DiInterface $dependencyInjector)
    {
        $this->di = $dependencyInjector;
    }

    /**
     * Returns the internal dependency injector
     *
     * @return \Phalcon\DiInterface
     */
    public function getDI()
    {
        return $this->di;
    }

    /**
     * 获取缓存对象
     * @todo
     * @param $cache 缓存配置
     */
    public function getServer(array $options = [], array $cache = [])
    {
        if (is_array($options)) {
            ! empty($options['type']) && $this->type = $options['type'];
        } else {
            $options && $this->type = $options;
        }
        $defaultDi = $this->getDI();
        $module = empty($options['module']) ? '' : $options['module'];
        if (! empty($module)) {
            $cacheMaster = 'cache' . $module;
            if (isset($defaultDi[$cacheMaster]) && empty($options['type'])) {
                return $defaultDi[$cacheMaster];
            }
            $file = ROOT_PATH . '/application/' . $module . '/config/' . APPLICATION_ENV . '/cache.php';
            $cache = is_file($file) ? include $file : [];
        }
        
        if (empty($cache)) {
            $config = $defaultDi->getConfig();
            $cache = $config->cache->toArray();
        }
        $backendType = $cache['backend'];
        $loginMemcache = 'login' == $this->type ? ucfirst($this->type) : '';
        $backend = 'Swallow\\Cache\\Backend\\' . $backendType . $loginMemcache;
        $frontend = 'Swallow\\Cache\\Frontend\\' . $cache['frontend'];
        $backendOptions = $cache[$backendType];
        if (isset($backendOptions['servers'][$this->type])) {
            $backendOptions['servers'] = $backendOptions['servers'][$this->type];
        }
        $frontCache = $defaultDi->get($frontend, [['lifetime' => $cache['lifetime']]]);
        $backCache = $defaultDi->get($backend, [$frontCache, $backendOptions]);
        return $backCache;
    }
}
