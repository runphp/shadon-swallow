<?php
/*
 * PHP version 5.5
 *
 * @copyright Copyright (c) 2012-2017 EELLY Inc. (http://www.eelly.com)
 * @link      http://www.eelly.com
 * @license   衣联网版权所有
 */

namespace Swallow\Base;

use Swallow\Core\Base;
use Swallow\Core\Conf;

/**
 * 控制器层
 *
 * @author     SpiritTeam
 *
 * @since      2015年1月12日
 *
 * @version    1.0
 */
abstract class Controller extends Base
{
    /**
     * @var \Phalcon\Http\Request
     */
    protected $request;

    /**
     * @var \Phalcon\Http\Response
     */
    protected $response;

    /**
     * @var int
     */
    protected $uid;

    /**
     *
     * @var int
     */
    protected $uidType;

    /**
     * App的对象
     *
     * @var \ECBaseApp
     */
    protected $app = null;

    /**
     * 构造器.
     *
     * @param \ECBaseApp $app
     */
    final protected function __construct($app)
    {
        $this->app = $app;
        $this->init();
    }

    /**
     *
     * @param object $app
     * @return static
     */
    public static function getInstance($app = null)
    {
        $class = static::class;
        static $controller;
        if (null === $controller) {
            $controller = new $class($app);
        }
        return $controller;
    }

    /**
     * @return int
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since  2017年5月3日
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * @param int $uid
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since  2017年5月3日
     */
    public function setUid($uid)
    {
        $this->uid = $uid;
    }

    /**
     *
     * @return int
     */
    public function getUidType()
    {
        return $this->uidType;
    }

    /**
     *
     * @param int $uidType
     */
    public function setUidType($uidType)
    {
        $this->uidType = $uidType;
    }

    /**
     * 获取参数唯一值
     *
     * @param string $className
     * @param array  $args
     *
     * @return string
     */
    protected static function getStaticKey($className, array $args)
    {
        return md5($className.':'.get_class($args[0]));
    }

    /**
     * 初始化.
     */
    protected function init()
    {
        $di = \Phalcon\Di::getDefault();
        $this->request = $di->getRequest();
        $this->response = $di->getResponse();
    }

    /**
     * 赋值
     *
     * @param string $k
     * @param mixed  $v
     */
    protected function assign($k, $v = null)
    {
        $this->app->assign($k, $v);
    }

    /**
     * 显示模版.
     *
     * @param string $n
     * @param bool   $return
     */
    protected function display($n, $return = false)
    {
        return $this->app->display($n, $return);
    }

    /**
     * 获取用户id.
     *
     * @author 林志刚<linzhigang@eelly.net>
     *
     * @since  2015年1月28日
     */
    protected function getUserId()
    {
        static $userId = null;

        return isset($userId) ? $userId : ($userId = (int) ($this->app->visitor->get('user_id')));
    }

    /**
     * 获取管理店铺id.
     *
     * @author 林志刚<linzhigang@eelly.net>
     *
     * @since  2015年1月28日
     */
    protected function getStoreId()
    {
        static $storeId = null;

        return isset($storeId) ? $storeId : ($storeId = (int) ($this->app->visitor->get('manage_store')));
    }

    /**
     * 提示警告信息.
     *
     *
     * @param string $key    提示信息
     * @param string $info   返回标识语
     * @param string $link   跳转地址
     * @param string $linkTo 跳转地址标识语
     * @param string $time   自动跳转时间
     * @author崔展铭<cuizhanming@eelly.net>
     *
     * @since  2015年11月30日
     */
    protected function showWarning($key, $info = null, $link = null, $linkTo = null, $time = 0)
    {
        if (empty($key)) {
            return false;
        }
        $_SESSION['showWarning'] = [$key, $info, $link, $linkTo];
        $_SESSION['changeTime'] = $time;
        $this->redirect(Conf::get('System/inc/SITE_URL').'/index.php?app=reminder&act=showTheWarning');
    }

    /**
     * 提示信息.
     *
     *
     * @param string      $key    提示信息
     * @param string      $info   返回标识语
     * @param string      $link   跳转地址
     * @param string      $linkTo 跳转地址标识语
     * @param boolean/int $time   自动跳转时间
     * @author崔展铭<cuizhanming@eelly.net>
     *
     * @since  2015年11月30日
     */
    protected function showMessage($key, $info = null, $link = null, $linkTo = null, $time = 0)
    {
        if (empty($key)) {
            return false;
        }
        $_SESSION['showMessage'] = [$key, $info, $link, $linkTo];
        $_SESSION['changeTime'] = $time;
        $this->redirect(Conf::get('System/inc/SITE_URL').'/index.php?app=reminder&act=showTheMessage');
    }

    /**
     * 显示对话框.
     *
     *
     * @param string $info   提示信息
     * @param string $link   跳转地址
     * @param string $linkTo 跳转地址标识语
     * @author崔展铭<cuizhanming@eelly.net>
     *
     * @since  2015年11月30日
     */
    protected function showDialog($info, $link = null, $linkTo = null)
    {
        if (empty($info)) {
            return false;
        }
        $_SESSION['showDialog'] = [$info, $link, $linkTo];
        $_SESSION['HTTP_REFERER'] = $_SERVER['HTTP_REFERER'];
        $this->redirect(Conf::get('System/inc/SITE_URL').'/index.php?app=reminder&act=showTheDialog');
    }

    /**
     * 重定向.
     *
     *
     * @param string $url  重定向地址
     * @param number $code
     * @author崔展铭<cuizhanming@eelly.net>
     *
     * @since  2015年12月2日
     */
    protected function redirect($url, $code = 302)
    {
        header('Location: '.$url, true, $code);
        exit;
    }

    /**
     * 判断是否清除缓存.
     *
     * @return bool
     */
    protected function isClearCache()
    {
        static $r = null;
        if (isset($r)) {
            return $r;
        }
        if ((isset($_GET['clear']) && $_GET['clear'] == 'cache') && (DEBUG_MODE || $_ENV['isInternalUser'])) {
            $r = true;
        } else {
            $r = false;
        }

        return $r;
    }

    protected function renderJson(array $content = null, $code = 200, $message = 'OK')
    {
        $this->response->setContentType('application/json');
        //$this->response->setStatusCode($code, $message);
        $this->response->setJsonContent([
            'code' => $code,
            'msg' => $message,
            'content' => $content,
        ]);
        $callback = $this->request->get('callback');
        if ($callback) {
            $content = $callback.'('.$this->response->getContent();
            $this->response->setContent($content);
            $this->response->appendContent(')');
        }
        $this->response->send();
    }
}
