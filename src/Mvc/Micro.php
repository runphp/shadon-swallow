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
 * micro app
 *
 * @author    何辉<hehui@eely.net>
 * @since     2015年9月11日
 * @version   1.0
 */
class Micro extends \Phalcon\Mvc\Micro
{
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
                'path' => "application/$name/src/$ucfirstName/Module.php",
            ];
            $clazzes[$completeModules[$name]['className']] = $completeModules[$name]['path'];
        }
        $loader->registerClasses($clazzes);
//         return parent::registerModules($completeModules, $merge);
    }

    public function bootstrap()
    {
        $this->get('/say/welcome/{name}', function ($name) {
            echo "<h1>Welcome $name!</h1>";
           });
        $this->handle();
    }
}