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
use Swallow\Exception\LogicException;
use Swallow\Exception\StatusCode;

/**
 * 模块 -> 服务基类
 * 对外接口的编写.
 *
 * @author     SpiritTeam
 *
 * @since      2015年1月12日
 *
 * @version    1.0
 */
abstract class Service extends Base
{
    /**
     * 当前用户id.
     *
     * @var int
     */
    private static $uid;

    /**
     * 当前用户类型.
     *
     * 1.店家 2.厂家 3.百里挑一
     *
     * @var int
     */
    private static $uidType;

    /**
     * 构造器.
     */
    final protected function __construct()
    {
        if (func_num_args()) {
            call_user_func_array([$this, 'init'], func_get_args());
        } else {
            $this->init();
        }
    }

    /**
     * @param int $uid
     *
     * @return static
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since  2016年12月22日
     */
    public function setUid($uid)
    {
        self::$uid = $uid;

        return $this;
    }

    /**
     * 获取用户id.
     *
     *
     * @throws LogicException
     *
     * @return number
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since  2016年12月23日
     */
    public function getUid()
    {
        if (0 == self::$uid) {
            throw new LogicException('未登录', StatusCode::REQUEST_FORBIDDEN);
        }

        return self::$uid;
    }

    /**
     * @param int $uidType
     *
     * @return static
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since  2016年12月22日
     */
    public function setUidType($uidType)
    {
        self::$uidType = $uidType;

        return $this;
    }

    /**
     * 获取用户类型.
     *
     *
     * @return number
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since  2016年12月22日
     */
    public function getUidType()
    {
        return self::$uidType;
    }

    /**
     * 获取app类型 1.店家 2.厂家 3.百里挑一
     *
     *
     * @return number
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since  2016年12月23日
     * @deprecated
     */
    public function getAppType()
    {
        return $this->getUidType();
    }

    /**
     * 获取服务代理.
     *
     * @param array  $options
     * @param string $options['loginToken']  登录token
     * @param string $options['accessToken'] 访问token
     * @param string $options['clientName']  客户端名称，默认ios
     *
     * @return self
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since  2017年3月30日
     */
    public static function getServiceProxy($options = [])
    {
        $di = \Phalcon\Di::getDefault();

        return $di->get(
            \Swallow\Service\ServiceProxy::class,
            [$di->get('serviceHttpClient'), get_called_class(), $di->getConfig()->apiUser, $options]
        );
    }

    /**
     * 初始化.
     *
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since  2016年12月23日
     */
    protected function init()
    {
        // 默认使用app的用户回话数据
        if (empty(self::$uid)) {
            $this->setUid((int) \Swallow\Di\Di::getInstance()->getShared('clientInfoNew')->getLoginUserInfo('uid'));
        }
        if (empty(self::$uidType)) {
            $type = \Swallow\Di\Di::getInstance()->getShared('clientInfoNew')->getClientInfo('client_user_type');
            $typeId = 0;
            switch ($type) {
                // 厂家
                case 'seller':
                    $typeId = 2;
                    break;
                    // 店家
                case 'buyer':
                    $typeId = 1;
                    break;
            }
            $this->setUidType($typeId);
        }
    }
}
