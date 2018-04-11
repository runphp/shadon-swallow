<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Console;

class Application extends \Phalcon\CLI\Console
{

    public function setDI(\Phalcon\DiInterface $dependencyInjector)
    {
        parent::setDI($dependencyInjector);
        $this->setEventsManager($dependencyInjector->getEventsManager());
    }

    /**
     * 注册标准模块.
     *
     * 自动把模块名转为下面格式
     * <code>
     * [
     *   'className' => 'Name\Module',
     *   'path' => 'application/name/src/Name/Module.php'
     * ]
     * </code>
     *
     * @param array $modules
     * @param bool $merge
     * @return \Phalcon\Mvc\Application
     * @author 何辉<hehui@eely.net>
     * @since  2015年9月6日
     */
    public function registerStandardModules(array $modules, $merge = false)
    {
        $loader = $this->getDI()->getLoader();
        
        $completeModules = [];
        $clazzes = [];
        foreach ($modules as $name) {
            $ucfirstName = ucfirst($name);
            $completeModules[$name] = [
                'className' => $ucfirstName . '\Module', 
                'path' => "application/$name/src/$ucfirstName/Module.php"];
            $clazzes[$completeModules[$name]['className']] = $completeModules[$name]['path'];
        }
        $loader->registerClasses($clazzes)->register();
        return $this->registerModules($completeModules);
    }

    public function bootstrap()
    {
        global $argv;
        if (empty($argv[1]) || empty($argv[2]) || empty($argv[3]) || ! isset($argv[1]) || ! isset($argv[2]) || ! isset($argv[3])) {
            echo "unknow command!\n";
            exit();
        }
        // 定义全局的参数， 设定当前任务及动作
        define('CURRENT_MODULE', (isset($argv[1]) ? $argv[1] : null));
        define('CURRENT_TASK',   (isset($argv[2]) ? $argv[2] : null));
        define('CURRENT_ACTION', (isset($argv[3]) ? $argv[3] : null));
        
        /**
         * 处理console应用参数
         */
        $arguments = array();
        foreach ($argv as $k => $arg) {
            if ($k == 1) {
                $arguments['module'] = $arg;
            } elseif ($k == 2) {
                $arguments['task'] = $arg;
            } elseif ($k == 3) {
                $arguments['action'] = $arg;
            } elseif ($k >= 4) {
                $arguments['params'][] = $arg;
            }
        }
        // 处理参数
        $this->handle($arguments);
    }
}