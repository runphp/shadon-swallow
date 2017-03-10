<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Base;

use Swallow\Core\Base;

/**
 * 控制器层
 * 
 * @author     SpiritTeam
 * @since      2015年1月12日
 * @version    1.0
 */
abstract class Controller extends Base
{

    /**
     * App的对象
     * @var \ECBaseApp
     */
    protected $app = null;

    /**
     * 获取参数唯一值
     *
     * @param  string $className
     * @param  array  $args
     * @return string
     */
    protected static function getStaticKey($className, array $args)
    {
        return md5($className . ':' . get_class($args[0]));
    }

    /**
     * 构造器
     * 
     * @param \ECBaseApp $app
     */
    final protected function __construct(\ECBaseApp $app)
    {
        $this->app = $app;
        $this->init();
    }

    /**
     * 初始化
     */
    protected function init()
    {
    }

    /**
     * 赋值
     * 
     * @param string $k
     * @param mixed $v
     */
    protected function assign($k, $v = null)
    {
        $this->app->assign($k, $v);
    }

    /**
     * 显示模版
     * 
     * @param string $n
     * @param boolean $return
     */
    protected function display($n, $return = false)
    {
        return $this->app->display($n, $return);
    }

    /**
     * 获取用户id
     * 
     * @author 林志刚<linzhigang@eelly.net>
     * @since  2015年1月28日
     */
    protected function getUserId()
    {
        static $userId = null;
        return isset($userId) ? $userId : ($userId = intval($this->app->visitor->get('user_id')));
    }

    /**
     * 获取管理店铺id
     * 
     * @author 林志刚<linzhigang@eelly.net>
     * @since  2015年1月28日
     */
    protected function getStoreId()
    {
        static $storeId = null;
        return isset($storeId) ? $storeId : ($storeId = intval($this->app->visitor->get('manage_store')));
    }
}