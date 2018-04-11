<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Module;

use Swallow\Mvc\View;
use Phalcon\Db\Adapter\Pdo\Mysql as DbAdapter;
use Phalcon\Mvc\View\Engine\Volt as VoltEngine;
use Swallow\Cache\CacheManage;
use Phalcon\Events\Manager as EventsManager;
use Phalcon\Db\Profiler as DbProfiler;

/**
 * 模块初始化基类.
 *
 * @author    何辉<hehui@eely.net>
 * @since     2015年8月28日
 * @version   1.0
 */
abstract class AbstractModule implements \Phalcon\Mvc\ModuleDefinitionInterface
{

    /**
     * 模块namespace
     *
     * @var string
     */
    protected $namespace;

    /**
     * 模块根路径
     *
     * @var string
     */
    protected $moduleRootPath;

    /**
     * Registers an autoloader related to the module
     *
     * @param mixed $di
     */
    public function registerAutoloaders(\Phalcon\DiInterface $di = null)
    {
        $this->namespace = str_replace('\\Module', '', get_class($this));
        // console app.
        if (null === $di) {
            $di = \Phalcon\Di::getDefault();
            $di->getDispatcher()->setNamespaceName($this->namespace . '\Console');
        }
        $loader = $di->getLoader();
        $this->moduleRootPath = 'application/' . strtolower($this->namespace);
        $loader->registerNamespaces([$this->namespace => $this->moduleRootPath . '/src/' . $this->namespace], true)->register();
    }

    /**
     * register config service.
     *
     * @param \Phalcon\DiInterface $di
     * @author 何辉<hehui@eely.net>
     * @since  2015年8月28日
     */
    abstract public function registerConfigService($di);

    /**
     * register event service.
     *
     * @param \Phalcon\DiInterface $di
     * @author 何辉<hehui@eely.net>
     * @since  2015年8月28日
     */
    abstract public function registerEventService($di);

    /**
     * Registers services related to the module
     *
     * TODO 服务进行拆分
     *
     * @param mixed $dependencyInjector
     */
    public function registerServices(\Phalcon\DiInterface $defaultDi)
    {
        //\Swallow\Di\Space::apply(strtolower($this->namespace));
        
        /**
         * Read configuration
         * 公共模块读取默认配置就可以了
         */
        $config = $defaultDi->getConfig();
        /* $defaults = $defaultDi['router']->getDefaults();
        if (strtolower($this->namespace) != $defaults['module']) {
            $config = $defaultDi->getConfig()->mergeArray(include $this->getModuleRootPath() . '/config/config.php');
        } */
        
        /**
         * fis3资源管理
         */
        $defaultDi['resource'] = function () use($defaultDi)
        {
            $resource = $defaultDi->getShared('\Swallow\Mvc\View\Resource');
            $resource::setConfig(['configDir' => ROOT_PATH . '/resource/config', 'templateDir' => ROOT_PATH . '/resource']);
            return $resource;
        };
        
        /**
         * 默认数据库
         */
        $defaultDi['dbMaster'] = function () use($config, $defaultDi)
        {
            $connection = $defaultDi->getShared('\Swallow\Db\Mysql', [$config->db->master->toArray()]);
            return $connection;
        };
        $defaultDi['db'] = $defaultDi['dbSlave'] = function () use($config, $defaultDi)
        {
            $slaves = $config->db->slave->toArray();
            $randKey = array_rand($slaves, 1);
            $connection = $defaultDi->get('\Swallow\Db\Mysql', [$slaves[$randKey]]);
            return $connection;
        };
        
        /**
         * 缓存管理器
         */
        $defaultDi['cacheManager'] = function () use($defaultDi)
        {
            return $defaultDi->get('\Swallow\Cache\CacheManager');
        };
        
        /**
         * 默认缓存
         */
        $defaultDi['cache'] = function () use($config, $defaultDi)
        {
            $cache = $config->cache->toArray();
            return $defaultDi['cacheManager']->getServer([], $cache);
        };
        
        /**
         * Redis管理器
         */
        $defaultDi['redisManager'] = function () use($defaultDi)
        {
            return $defaultDi->get('\Swallow\Redis\RedisManager');
        };
        
        /**
         * Redis
         */
        $defaultDi['redis'] = function () use($config, $defaultDi)
        {
            $redis = $config->redis->toArray();
            return $defaultDi['redisManager']->getServer([], $redis);
        };
        
        /**
         * session
         */
        $defaultDi['session'] = function () use($config, $defaultDi)
        {
            $seesionType = $config->session->adapter;
            $sessionAdapter = 'Swallow\\Session\\Adapter\\' . $seesionType;
            $sessionCconfig = $config->session[$seesionType];
            $session = $defaultDi->getShared($sessionAdapter, [$sessionCconfig->toArray()]);
            $session->start();
            return $session;
        };
        
        /**
         * cookies
         */
        $defaultDi['cookies'] = function () use($defaultDi)
        {
            $cookies = $defaultDi->getShared('\Swallow\Http\Response\Cookies');
            $cookies->useEncryption(true);
            return $cookies;
        };
        
        /**
         * crypt
         */
        $defaultDi['crypt'] = function () use($config, $defaultDi)
        {
            $crypt = $defaultDi->getShared('\Swallow\Cipher\Crypt');
            $crypt->setKey($config->appKey);
            $crypt->setPadding(1);
            return $crypt;
        };
        
        // attach logic default listener.
        $eventsManager = $defaultDi->getEventsManager();
        $eventsManager->attach('logic', $defaultDi->getShared('Swallow\Service\LogicListener'));
        
        $this->registerConfigService($defaultDi);
        
        $this->registerEventService($defaultDi);
    }

    /**
     * 获取当前模块的根路径.
     *
     * @return string
     * @author 何辉<hehui@eely.net>
     * @since  2015年9月2日
     */
    public function getModuleRootPath()
    {
        return $this->moduleRootPath;
    }
}