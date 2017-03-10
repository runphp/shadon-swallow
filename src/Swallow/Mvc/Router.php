<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Mvc;

/**
 * 路由
 *
 * @author     SpiritTeam
 * @since      2015年8月13日
 * @version    1.0
 */
class Router extends \Phalcon\Mvc\Router
{
    public function setDI(\Phalcon\DiInterface $dependencyInjector)
    {
        parent::setDI($dependencyInjector);
        //$this->initDefault();
    }

    /**
     * 初始化默认路由.
     */
    public function initDefault()
    {
        // set default home page
        $this->add('/',[
            'namespace' => 'Common\Controller',
            'module' => 'common',
            'controller' => 'index',
            'action' => 'index',
        ]);
        $di = $this->getDI();
        foreach ($di['application']->getApplication()->getModules() as $key => $module) {
            $namespace = str_replace('Module', 'Controller', $module["className"]);
            $this->add('/' . $key . '/:params',
                [
                    'namespace' => $namespace,
                    'module' => $key,
                    'controller' => 'index',
                    'action' => 'index',
                    'params' => 1
                ]
            )->setName($key);
            $this->add('/' . $key . '/:controller/:params',
                [
                    'namespace' => $namespace,
                    'module' => $key,
                    'controller' => 1,
                    'action' => 'index',
                    'params' => 2
                ]
            );
            $this->add('/' . $key . '/:controller/:action/:params',
                [
                    'namespace' => $namespace,
                    'module' => $key,
                    'controller' => 1,
                    'action' => 2,
                    'params' => 3
                ]
            ) ->convert('action', function ($action) {
                 return lcfirst(
                            preg_replace_callback('/_([a-zA-Z])/', function ($match) {
                                return strtoupper($match[1]);
                            }, $action)
                         );
            });
            APP_DEBUG && $this->add('/front_debug/'.$key.'/(\w+(\/?\w+)*$)',
                [
                    'namespace' => $namespace,
                    'module' => $key,
                    'controller' => '',
                    'action' => 'frontDebug',
                    'view' => 1
                ]
            );
        }
        $this->setDefaultModule('common');
        $this->setDefaultNamespace('Common\Controller');
        $this->setDefaults(['controller' => 'error', 'action' => 'notFound']);
        //$this->addAll();
    }

    /**
     * 废弃方法  （当前状态）
     * Adds a route to the router without any HTTP constraint
     */
    public function addAll()
    {
        $config = $this->getDI()->getConfig();
        $router = $config->router->toArray();
        if (empty($router)) {
            return false;
        }
        foreach ($router as $key => $val) {
            if (empty($val['controller'])) {
                throw new \ErrorException('controller can not empty!');
            }
            $controllerArr = explode('\\', $val['controller']);
            $count = count($controllerArr);
            if ($count < 3) {
                throw new \ErrorException('controller is error!');
            }
            $namespace = array_slice($controllerArr, 0, $count - 1);
            $namespace = implode('\\', $namespace);
            $module = strtolower($controllerArr['0']);
            $controller = strtolower($controllerArr[$count - 1]);
            $action = empty($val['action']) ? 'index' : $val['action'];
            $httpMethods = empty($val['type']) ? null : $val['type'];
            $paths = [
                'namespace' => $namespace,
                'module' => $module,
                'controller' => $controller,
                'action' => $action
            ];
            ! empty($val['params']) && $paths['params'] = $val['params'];
            if(! empty($val['name'])){
                $this->add($key, $paths, $httpMethods)->setName($val['name'])->convert('action', function ($action) {
                    return lcfirst(
                        preg_replace_callback('/_([a-zA-Z])/', function ($match) {
                            return strtoupper($match[1]);
                        }, $action)
                     );
                });
            }else {
                $this->add($key, $paths, $httpMethods)->convert('action', function ($action) {
                    return lcfirst(
                        preg_replace_callback('/_([a-zA-Z])/', function ($match) {
                            return strtoupper($match[1]);
                        }, $action)
                     );
                });
            }
        }
    }
}
