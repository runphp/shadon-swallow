<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Loader;

/**
 * 加载器.
 *
 * @author     SpiritTeam
 * @since      2015年8月13日
 * @version    1.0
 */
class Loader extends \Phalcon\Loader implements \Phalcon\Di\InjectionAwareInterface
{

    protected $di;

    public function setDI(\Phalcon\DiInterface $di)
    {
        $this->di = $di;
    }

    public function getDI()
    {
        return $this->di;
    }

    /**
     * 类名
     * @var string
     */
    private $className = '';

    /**
     * 代理文件路径
     * @var string
     */
    private $proxyFilePath = '';

    /**
     * 缓存路径
     * @var string
     */
    public $proxyDir = '';

    /**
     * (non-PHPdoc).
     * @see \Phalcon\Loader::autoLoad()
     */
    public function autoLoad($className)
    {
        $result = parent::autoLoad($className);
        //$moduleName = $this->di['router']->getModuleName();
        if (false === $result) {
            $result = $this->registerLoadClass($className);
        }
        //更新代理文件
        $this->updateAnnotations($className);
        //验证代码
        /*if (APP_DEBUG) {
            $verify = $this->getDI()->getShared('\Swallow\Debug\VerifyCode');
            $verify->verify($className);
        }*/
        return $result;
    }

    /**
     * 注册加载不存在文件
     *
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年10月28日
     */
    public function registerLoadClass($className)
    {
        $classArr = explode('\\', $className);
        $moduleName = $classArr[0];
        //加载远程模块
        $modules = $this->di['config']->modulesService->toArray();
        //$remoteServices = $this->getRemoteService();
        if (isset($classArr[1]) && $classArr[1] == 'Service' && array_key_exists($moduleName, $modules)) {
            $this->load($className);
            return true;
        }
        //加载本地模块
        $modules = $this->di['application']->getApplication()->getModules();
        if (array_key_exists(strtolower($moduleName), $modules)) {
            $moduleClass = $moduleName . '\\Module';
            $moduleObject = $this->di->getShared($moduleClass);
            $moduleObject->registerAutoloaders($this->di);
            $moduleObject->registerServices($this->di);
            $result = parent::autoLoad($className);
            return $result;
        }
    }

    /**
     * 更新代理文件
     * 
     * @param $className
     *
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年10月28日
     */
    public function updateAnnotations($className)
    {
        $classArr = explode('\\', $className);
        if (! isset($classArr[1]) || $classArr[1] !== 'Logic') {
            return true;
        }
        $annotationsName = str_replace('\\', '_', strtolower($className));
        $annotationsPath = ROOT_PATH . '/temp/annotations/' . $annotationsName . '.php';
        if (file_exists($annotationsPath)) {
            $classPath = ROOT_PATH . '/application/' . strtolower($classArr[0]) . '/src/' . str_replace('\\', '/', $className) . '.php';
            $classMTime = file_exists($classPath) ? filemtime($classPath) : 0;
            $annotationsMTime = filemtime($annotationsPath);
            if ($classMTime > $annotationsMTime) {
                unlink($annotationsPath);
            }
        }
    }

    /**
     * 加载文件
     *
     * @param $className
     * @return \ReflectionMethod
     */
    public function load($className)
    {
        $this->className = $className;
        $this->proxyDir = ROOT_PATH . '/temp/proxy/';
        $this->proxyFilePath = $this->proxyDir . str_replace('\\', '/', $this->className) . '.php';
        if (! $this->checkVaild()) {
            $this->build();
        }
        include $this->proxyFilePath;
    }

    /**
     * 检查代理文件
     *
     * @return boolean
     */
    private function checkVaild()
    {
        $proxyPath = $this->proxyFilePath;
        if (! file_exists($proxyPath)) {
            return false;
        }
        return true;
    }

    /**
     * 开始生成文件
     *
     * @return void
     */
    private function build()
    {
        $proxyClassPos = strrpos($this->className, '\\');
        $proxyClass = substr($this->className, $proxyClassPos + 1);
        $proxyClassPath = substr($this->className, 0, $proxyClassPos);
        
        $content = '<?php' . PHP_EOL . 'namespace ' . $proxyClassPath . ';';
        $content .= PHP_EOL . 'use \Swallow\Service\Client  as Traget;';
        $content .= PHP_EOL . '/**';
        $content .= PHP_EOL . ' * @author    auto loader';
        $content .= PHP_EOL . ' * @since     ' . date('Y-m-d H:i:s');
        $content .= PHP_EOL . ' * @version   1.0';
        $content .= PHP_EOL . ' */';
        $content .= PHP_EOL . 'class ' . $proxyClass . ' extends Traget';
        $content .= PHP_EOL . '{';
        $content .= PHP_EOL . '}';
        $this->putFile($content);
    }

    /**
     * 写入文件
     *
     * @param string $content
     * @return boolean
     */
    private function putFile($content)
    {
        $paths = $this->proxyFilePath;
        if (! file_exists($paths)) {
            $start = strlen($this->proxyDir);
            while (false !== ($search = strpos($paths, '/', $start))) {
                $path = substr($paths, 0, $search);
                if (! file_exists($path)) {
                    mkdir($path, 0755, true);
                }
                $start = $search + 1;
            }
        }
        file_put_contents($paths, $content);
    }

    /**
     * 根据当前服务器的IP+配置，返回远程服务
     *
     * @return  array
     * @author  chenjinggui<chenjinggui@eelly.com>
     * @since   2015年10月23日
     */
    private function getRemoteService()
    {
        $di = $this->di;
        $modules = $di['config']->modulesAddr->toArray();
        $local = $_SERVER['SERVER_ADDR'];
        $remoteService = [];
        foreach ($modules as $k => $v) {
            if (! in_array($local, $v)) {
                $remoteService[$k] = 1;
            }
        }
        return $remoteService;
    }
}
