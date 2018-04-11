<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */

namespace Swallow\Unit;

/**
 * 应用基类
 *
 * @author     SpiritTeam
 * @since      2015年8月13日
 * @version    1.0
 */
class Application extends \Phalcon\Mvc\Application
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
         $loader->registerClasses($clazzes)->register();
        return parent::registerModules($completeModules);
    }

    /**
     * 初始化
     *
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年9月14日
     */
    public function bootstrap()
    {
        // 处理参数
        //$this->handle($arguments);
        //echo $this->handle()->getContent();
    }
}
