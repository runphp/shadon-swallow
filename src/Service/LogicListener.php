<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Service;

/**
 * logic 默认监听器.
 *
 * @author    何辉<hehui@eely.net>
 * @since     2015年8月31日
 * @version   1.0
 */
class LogicListener implements \Phalcon\Di\InjectionAwareInterface
{

    protected $di;

    protected $caches = [];

    protected $module;

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
     * 前置
     * 
     * @param $event
     * @param $logicProxy
     * @param $args
     * @return Ambigous <unknown, boolean, string, multitype:>
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年10月14日
     */
    public function beforeMethod($event, LogicProxy $logicProxy, $args)
    {
        return $this->cacheAnnotations($event, $logicProxy, [$args, false]);
    }

    /**
     * 后置
     * 
     * @param $event
     * @param $logicProxy
     * @param $argsReturn
     * @return Ambigous <unknown, boolean, string, multitype:>
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年10月14日
     */
    public function afterMethod($event, LogicProxy $logicProxy, array $argsReturn)
    {
        return $this->cacheAnnotations($event, $logicProxy, $argsReturn);
    }

    /**
     * 注释
     * 
     * @param $event
     * @param $logicProxy
     * @param $argsReturn
     * @return Ambigous <boolean, string, multitype:>|unknown
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年10月14日
     */
    private function cacheAnnotations($event, LogicProxy $logicProxy, array $argsReturn)
    {
        $class = $logicProxy->getLogicClass();
        $this->module = strtolower(explode('\\', $class)[0]);
        // 解析目前访问的逻辑层方法的注释
        $annotations = $this->di->getAnnotations()->getMethod($class, $logicProxy->getActiveMethod());
        // 检查是否方法中带有注释名称‘Cache’的注释单元
        if ($annotations->has('Cache')) {
            // 这个方法带有‘Cache’注释单元
            $annotation = $annotations->get('Cache');
            // 获取注释单元的‘lifetime’参数
            $lifetime = $annotation->getNamedParameter('lifetime');
            $options = array('lifetime' => $lifetime);
            // 检查注释单元中是否有用户定义的‘key’参数
            if ($annotation->hasArgument('key')) {
                $options['key'] = $annotation->getArgument('key');
            } else {
                $options['key'] = md5(serialize([$class, $logicProxy->getActiveMethod(), $argsReturn[0]]));
            }
            return $this->cache($options, $argsReturn[1]);
        }
        return $argsReturn[1];
    }

    /**
     * 进行缓存
     *
     * @param array $options
     * @author 何辉<hehui@eely.net>
     * @since  2015年8月31日
     */
    private function cache(array $options, $data = false)
    {
        $defaultDi = $this->getDI();
        $module = $this->module;
        $cacheMaster = 'cache';
        if (! isset($defaultDi[$cacheMaster . $module])) {
            $file = ROOT_PATH . '/application/' . $module . '/config/' . APPLICATION_ENV . '/cache.php';
            if (is_file($file)) {
                $cache = include $file;
                if (! empty($cache) && isset($cache['backend']) && isset($cache['frontend'])) {
                    $cacheMaster .= $module;
                    $defaultDi[$cacheMaster] = function () use($cache, $defaultDi)
                    {
                        return $defaultDi['cacheManager']->getServer([], $cache);
                    };
                }
            }
        } else {
            $cacheMaster .= $module;
        }
        $cache = $defaultDi[$cacheMaster];
        $application = $defaultDi->getApplication();
        $appType = $application::APP_TYPE;
        $clearCache = $appType != 'console' ? $this->getDI()->getClearCache()->forceClearCache() : false;
        if (false !== $data || $clearCache === true) {
            $this->caches[$options['key']] = $data;
            $cache->save($options['key'], $data, $options['lifetime']);
            return $data;
        }
        if (! isset($this->caches[$options['key']])) {
            $data = $cache->get($options['key'], $options['lifetime']);
            if (null !== $data) {
                $this->caches[$options['key']] = $data;
            } else {
                return false;
            }
        }
        return $this->caches[$options['key']];
    }
}