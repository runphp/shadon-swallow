<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Mvc\View\Engine;

use Phalcon\Mvc\View\Engine\Php as PhpEngine;

/**
 * 模板函数
 * 
 * @author    姚礼伟<yaoliwei@eelly.net>
 * @since     2015年9月2日
 * @version   1.0
 */
class Php extends PhpEngine
{

    public $configs;

    /**
     * Phalcon\Mvc\View\Engine constructor
     *
     * @param mixed $view
     * @param mixed $dependencyInjector
     */
    public function __construct(\Phalcon\Mvc\ViewBaseInterface $view, \Phalcon\DiInterface $dependencyInjector = null)
    {
        parent::__construct($view, $dependencyInjector);
        $this->configs = $this->getConfig();
    }

    /**
     * 合并配置
     *
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年11月12日
     */
    public function getConfig()
    {
        $defaultDi = $this->getDI();
        $config = $defaultDi->getConfig()->toArray();
        $module = $this->router->getModuleName();
        $defaults = $defaultDi['router']->getDefaults();
        if ($module != $defaults['module']) {
            $file = ROOT_PATH . '/application/' . $module . '/config/config.php';
            $configModule = is_file($file) ? include $file : [];
            $config = array_merge($config, $configModule);
        }
        return $config;
    }

    /**
     * 设置前端加载器
     * 
     * @param [type] $id [description]
     */
    public function framework($id)
    {
        return $this->resource->framework($id);
    }

    /**
     * 添加标记位
     * @param  [type] $type [description]
     * @return [type]       [description]
     */
    public function placeHolder($type)
    {
        return $this->resource->placeHolder($type);
    }

    /**
     * 加载某个资源及其依赖
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    function import($id)
    {
        return $this->resource->import($id);
    }

    /**
     * styleStart
     */
    function styleStart()
    {
        return $this->resource->styleStart();
    }

    /**
     * styleEnd
     */
    function styleEnd()
    {
        return $this->resource->styleEnd();
    }

    /**
     * scriptStart
     */
    function scriptStart()
    {
        return $this->resource->styleStart();
    }

    /**
     * scriptEnd
     */
    function scriptEnd()
    {
        return $this->resource->scriptEnd();
    }

    /**
     * 获取模板名
     *
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年9月8日
     */
    public function getMainView()
    {
        return $this->view->getMainView();
    }

    /**
     * 获取控制器名
     *
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年9月8日
     */
    public function getControllerName()
    {
        return $this->view->getControllerName();
    }

    /**
     * 获取方法名
     *
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年9月8日
     */
    public function getActionName()
    {
        return $this->view->getActionName();
    }

    /**
     * 生成TokenKey
     *
     * @param int $numberBytes 
     * @return string
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年9月8日
     */
    public function getTokenKey($numberBytes = null)
    {
        return $this->security->getTokenKey($numberBytes);
    }

    /**
     * 生成Token
     *
     * @param int $numberBytes
     * @return string
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年9月8日
     */
    public function getToken($numberBytes = null)
    {
        return $this->security->getToken($numberBytes);
    }

    /**
     * 生成URL
     * 
     * @param mixed $uri 
     * @param mixed $args 
     * @param mixed $local 
     * @param mixed $baseUri 
     * @return string 
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年9月8日
     */
    public function url($uri = null, $args = null, $local = null, $baseUri = null)
    {
        is_array($uri) && $baseUri = $baseUri . '/';
        return $this->url->get($uri, $args, $local, $baseUri);
    }

    /**
     * 静态URL
     *
     * @param mixed $uri
     * @return string
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年9月8日
     */
    public function staticUrl($uri = null)
    {
        $staticArray = isset($this->configs['url']['static']) ? $this->configs['url']['static'] : [];
        $randKey = array_rand($staticArray, 1);
        $staticUrl = isset($staticArray[$randKey]) ? $staticArray[$randKey] : '';
        $staticUrl && $this->url->setStaticBaseUri($staticUrl);
        return $this->url->getStatic($uri);
    }

    /**
     * imgUrl
     * 
     * @param mixed $uri
     * @return string
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年9月8日
     */
    public function imgUrl($uri = null)
    {
        $imgArray = isset($this->configs['url']['img']) ? $this->configs['url']['img'] : [];
        $randKey = array_rand($imgArray, 1);
        $imgUrl = isset($imgArray[$randKey]) ? $imgArray[$randKey] : '';
        return $imgUrl . $uri;
    }

    /**
     * 客服url
     * 先统一调用方法，根据需要再修改
     *
     * @return  string
     * @author  chenjinggui<chenjinggui@eelly.net>
     * @since   2015年10月13日
     */
    public function kefuUrl()
    {
        return isset($this->configs['url']['kefu']) ? $this->configs['url']['kefu'] : '';
    }

    /**
     * 新商城URL
     *
     * @return string
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年9月8日
     */
    public function siteUrl()
    {
        return isset($this->configs['url']['site']) ? $this->configs['url']['site'] : '';
    }

    /**
     * 旧商城URL
     *
     * @return string
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年9月8日
     */
    public function mallUrl()
    {
        return isset($this->configs['url']['mall']) ? $this->configs['url']['mall'] : '';
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
        return $this->assets->registerCssFile($path, $isMerge, $prefix);
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
        return $this->assets->registerScriptFile($path, $isMerge, $prefix);
    }

    /**
     * escapeHtml
     *
     * @param string $text
     * @return string
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年9月8日
     */
    public function escapeHtml($text)
    {
        return $this->escaper->escapeHtml($text);
    }

    /**
     * escapeCss
     *
     * @param string $css
     * @return string
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年9月8日
     */
    public function escapeCss($css)
    {
        return $this->escaper->escapeCss($css);
    }

    /**
     * escapeJs
     *
     * @param string $js
     * @return string
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年9月8日
     */
    public function escapeJs($js)
    {
        return $this->escaper->escapeJs($js);
    }

    /**
     * escapeHtmlAttr
     *
     * @param string $attribute
     * @return string
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年9月8日
     */
    public function escapeHtmlAttr($attribute)
    {
        return $this->escaper->escapeHtmlAttr($attribute);
    }

    /**
     * escapeUrl
     *
     * @param string $url
     * @return string
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年9月8日
     */
    public function escapeUrl($url)
    {
        return $this->escaper->escapeUrl($url);
    }

    /**
     * 修改字符串中指定字符的样式
     * 
     * @param array $strArray 要修改的字符
     * @param string $content 要修改的字符串
     * @param string $style class样式 
     * @return string
     * @author zengzhihao<zengzhihao@eelly.net>
     * @since  2015年10月17日
     */
    public function strReplace(array $strArray, $content, $style)
    {
        $mapStrArray = array_map(
            function ($val) use($style)
            {
                return '<span class=' . $style . '>' . $val . '</span>';
            }, $strArray);
        
        return str_replace($strArray, $mapStrArray, $content);
    }
}
