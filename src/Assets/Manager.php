<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Assets;

/**
 *
 * @author     SpiritTeam
 * @since      2015年8月13日
 * @version    1.0
 */
class Manager extends \Phalcon\Assets\Manager implements \Phalcon\DI\InjectionAwareInterface
{

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
     * 注册CSS文件
     *
     * @param string $path 路径，位于resource目录下，可用逗号分隔
     * @param bool $isMerge 是否合并
     * @param string $prefix 名字前缀，用于区别合并到哪个css文件
     */
    public function registerCssFile($path, $isMerge = true, $prefix = 'eelly-')
    {
        $paths = preg_split("/[\s,]+/", $path);
        if (empty($paths)) {
            throw new \ErrorException('Path can not empty!');
        }
        $_name = '';
        $filetime = '';
        $config = $this->di->getConfig();
        $collection = $this->collection($path);
        $collection->setSourcePath('resource/');
        foreach ($paths as $val) {
            $_name .= $val;
            $fileName = 'resource/' . $val;
            if (! file_exists($fileName)) {
                throw new \ErrorException("$fileName is not exist");
            }
            $filetime += filemtime($fileName);
            $collection->addCss($val);
        }
        $_name = $prefix . substr(md5($_name), 0, 16);
        $name = $_name . dechex($filetime);
        $sonFolder = substr($name, strlen($prefix), 2);
        $dir = 'resource/assets/css/' . $sonFolder . '/';
        $prevName = substr($name, strlen($prefix), 16);
        if (! file_exists($dir . $name)) {
            if (is_dir($dir)) {
                if ($dh = opendir($dir)) {
                    while (($file = readdir($dh)) != false) {
                        if ($file != '.' && $file != '..') {
                            if ($prevName == substr($file, strlen($prefix), 16)) {
                                unlink($dir . $file);
                                break;
                            }
                        }
                    }
                    closedir($dh);
                }
            } elseif ($isMerge) {
                mkdir($dir, 0744, true);
            }
            $staticArray = $config->url->static->toArray();
            $randKey = array_rand($staticArray, 1);
            $staticUrl = isset($staticArray[$randKey]) ? $staticArray[$randKey] : '';
            $collection->setPrefix($staticUrl)->join($isMerge);
            if ($isMerge) {
                $collection->setTargetPath($dir . $name . '.css')
                    ->setTargetUri('/assets/css/' . $sonFolder . '/' . $name . '.css')
                    ->addFilter(new \Phalcon\Assets\Filters\Cssmin());
            }
        }
        
        $this->outputCss($path);
    }

    /**
     * 注册js文件
     *
     * @param string $path 路径，位于resource目录下，可用逗号分隔
     * @param bool $isMerge 是否合并
     * @param string $prefix 名字前缀，用于区别合并到哪个css文件
     *
     */
    public function registerScriptFile($path, $isMerge = true, $prefix = 'eelly-')
    {
        $paths = preg_split("/[\s,]+/", $path);
        if (empty($paths)) {
            throw new \ErrorException('Path can not empty!');
        }
        $_name = '';
        $filetime = '';
        $config = $this->di->getConfig();
        $collection = $this->collection($path);
        $collection->setSourcePath('resource/');
        foreach ($paths as $val) {
            $_name .= $val;
            $filetime .= filemtime('resource/' . $val);
            $collection->addCss($val);
        }
        $_name = $prefix . substr(md5($_name), 0, 16);
        $filetime = substr(md5($filetime), 0, 16);
        $name = $_name . $filetime;
        $sonFolder = substr($name, strlen($prefix), 2);
        $dir = 'resource/assets/js/' . $sonFolder . '/';
        $prevName = substr($name, strlen($prefix), 16);
        if (! file_exists($dir . $name)) {
            if (is_dir($dir)) {
                if ($dh = opendir($dir)) {
                    while (($file = readdir($dh)) != false) {
                        if ($file != '.' && $file != '..') {
                            if ($prevName == substr($file, strlen($prefix), 16)) {
                                unlink($dir . $file);
                                break;
                            }
                        }
                    }
                    closedir($dh);
                }
            } elseif ($isMerge) {
                mkdir($dir, 0744, true);
            }
            $staticArray = $config->url->static->toArray();
            $randKey = array_rand($staticArray, 1);
            $staticUrl = isset($staticArray[$randKey]) ? $staticArray[$randKey] : '';
            $collection->setPrefix($staticUrl)->join($isMerge);
            if ($isMerge) {
                $collection->setTargetPath($dir . $name . '.js')
                    ->setTargetUri('/assets/js/' . $sonFolder . '/' . $name . '.js')
                    ->addFilter(new \Phalcon\Assets\Filters\Jsmin());
            }
        }
        $this->outputJs($path);
    }
}
