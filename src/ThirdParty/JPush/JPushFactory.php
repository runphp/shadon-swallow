<?php
/*
 * PHP version 5.5
 *
 * @copyright Copyright (c) 2012-2016 EELLY Inc. (http://www.eelly.com)
 * @link      http://www.eelly.com
 * @license   衣联网版权所有
 */
namespace Swallow\ThirdParty\JPush;

use Swallow\Core\Conf;

/**
 * 极光工厂类
 *
 * @author    hehui<hehui@eelly.net>
 * @since     2016年10月25日
 * @version   1.0
 */
class JPushFactory
{

    /**
     *
     * @var array
     */
    private static $jpushObjects = [
        'Config' => null,
        'Client' => []
    ];

    /**
     * 创建极光客户端
     *
     * @param string $appName "store" 或 "factory"
     * @return \JPush\Client
     * @author hehui<hehui@eelly.net>
     * @since  2016年10月26日
     */
    public static function create($appName)
    {
        if (! isset(self::$jpushObjects['Client'][$appName])) {
            $config = Conf::get('jpush')[$appName];
            if (empty($config)) {
                throw new \RuntimeException('jpush config nonexist, \'cp config/example/config.jpush.php config/config.jpush.php\'');
            }
            self::$jpushObjects['Config'] = $config;
            self::$jpushObjects['Client'][$appName] = new \JPush\Client(
                $config['app_key'],
                $config['master_secret'],
                LOG_PATH.'/jpush_'.$appName.date('_Ymd').'.txt'
            );
        }
        return self::$jpushObjects['Client'][$appName];
    }

    /**
     * 创建极光PushPayload
     *
     *
     * @param string $appName "store" 或 "factory"
     * @return \JPush\PushPayload
     * @author hehui<hehui@eelly.net>
     * @since  2017年2月24日
     */
    public static function createPushPayload($appName)
    {
        return self::create($appName)->push()->options(self::$jpushObjects['Config']['options']);
    }
}
