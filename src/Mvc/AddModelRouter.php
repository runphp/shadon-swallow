<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Mvc;

class AddModelRouter implements \Swallow\Bootstrap\BootstrapInterface
{

    protected $di;

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

    public function bootStrap()
    {
        $this->addModuleRouter();
    }

    /**
     * Adds a route to the router without any HTTP constraint
     */
    public function addModuleRouter()
    {
        $di = $this->di;
        $routerObj = $di->getRouter();
        $routerObj->initDefault(); //注册默认路由
        $modules = $di['application']->getApplication()->getModules();
        if (! empty($modules)) {
            //$routerAll = [];
            //$defaults = $di['router']->getDefaults();
            //$defaultsModule = $defaults['module'];  || $module == $defaultsModule
            foreach ($modules as $module => $val) {
                $path = ROOT_PATH . '/application/' . $module . '/config/' . APPLICATION_ENV . '/router.php';
                $router = file_exists($path) ? include $path : [];
                if (empty($router)) {
                    continue;
                }
                //$routerAll = array_merge($routerAll, $router);
                foreach ($router as $key => $val) {
                    if (empty($val['controller'])) {
                        throw new \ErrorException('controller can not empty!');
                    }
                    $module = !empty($val['module']) ? strtolower($val['module']) : $module;
                    $namespace = ucfirst($module) . '\Controller';
                    $controller = !empty($val['controller']) ? $val['controller'] : 'index';
                    $action = ! empty($val['action']) ? $val['action'] : 'index';
                    $httpMethods = empty($val['type']) ? null : $val['type'];
                    $paths = ['namespace' => $namespace, 'module' => $module, 'controller' => $controller, 'action' => $action];
                    ! empty($val['params']) && $paths['params'] = $val['params'];
                    if (! empty($val['name'])) {
                        $routerObj->add($key, $paths, $httpMethods)->setName($val['name'])->convert('action', function ($action) {
                            return lcfirst(
                                preg_replace_callback('/_([a-zA-Z])/', function ($match) {
                                    return strtoupper($match[1]);
                                }, $action)
                            );
                        });
                    } else {
                        $routerObj->add($key, $paths, $httpMethods)->convert('action', function ($action) {
                            return lcfirst(
                                preg_replace_callback('/_([a-zA-Z])/', function ($match) {
                                    return strtoupper($match[1]);
                                }, $action)
                            );
                        });
                    }
                }
            }
            /* foreach ($routerAll as $key => $val) {
                if (empty($val['controller'])) {
                    throw new \ErrorException('controller can not empty!');
                }
                $module = !empty($val['module']) ? strtolower($val['module']) : $module;
                $namespace = ucfirst($module) . '\Controller';
                $controller = !empty($val['controller']) ? $val['controller'] : 'index';
                $action = ! empty($val['action']) ? $val['action'] : 'index';
                $httpMethods = empty($val['type']) ? null : $val['type'];
                $paths = ['namespace' => $namespace, 'module' => $module, 'controller' => $controller, 'action' => $action];
                ! empty($val['params']) && $paths['params'] = $val['params'];
                if (! empty($val['name'])) {
                    $routerObj->add($key, $paths, $httpMethods)->setName($val['name'])->convert('action', function ($action) {
                        return lcfirst(
                            preg_replace_callback('/_([a-zA-Z])/', function ($match) {
                                return strtoupper($match[1]);
                            }, $action)
                         );
                    });
                } else {
                    $routerObj->add($key, $paths, $httpMethods)->convert('action', function ($action) {
                        return lcfirst(
                            preg_replace_callback('/_([a-zA-Z])/', function ($match) {
                                return strtoupper($match[1]);
                            }, $action)
                         );
                    });
                }
            } */
            //$routerObj->handle(); //如果路由启动后，需要手动加载
        }
    }
}