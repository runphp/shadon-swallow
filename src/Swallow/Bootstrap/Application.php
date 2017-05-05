<?php
/*
 * PHP version 5.5
 *
 * @copyright Copyright (c) 2012-2017 EELLY Inc. (http://www.eelly.com)
 * @link      http://www.eelly.com
 * @license   衣联网版权所有
 */

namespace Swallow\Bootstrap;

/**
 * 应用启动.
 *
 * @author     SpiritTeam
 *
 * @since      2015年8月13日
 *
 * @version    1.0
 */
class Application
{
    // dev本地，local 待上线，prod 线上，test 测试
    const ENV_PRODUCTION = 'prod';

    const ENV_STAGING = 'local';

    const ENV_TEST = 'test';

    const ENV_DEVELOPMENT = 'dev';

    const APP_TYPE = 'web';

    /**
     * @var Application
     */
    protected $app;

    protected $bootstrappers = [];

    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * App环境判断.
     *
     * @param string $env
     *
     * @return bool
     *
     * @author 何辉<hehui@eely.net>
     *
     * @since  2015年9月7日
     */
    public static function environment($env)
    {
        return static::APP_TYPE == $env;
    }

    /**
     * bootstrap.
     *
     * @author 何辉<hehui@eely.net>
     *
     * @since  2015年8月21日
     */
    public function bootstrap()
    {
        echo $this->app->bootstrap();
    }

    public function getApplication()
    {
        return $this->app;
    }

    /**
     * 启动.
     *
     * @author 范世军<fanshijun@eelly.net>
     *
     * @since  2015年10月16日
     */
    public static function run()
    {
        $diClass = '\\Swallow\\Di\\'.ucfirst(static::APP_TYPE).'Di';
        $di = new $diClass();
        $di->setShared('application', new static($di->getApplication()));
        /* @var $application self */
        $application = $di->getApplication();
        $application->bootstrapWithStrappers();
        $application->bootstrap();
    }

    /**
     * 启动器.
     *
     * @author 范世军<fanshijun@eelly.net>
     *
     * @since  2015年10月16日
     */
    protected function bootstrapWithStrappers()
    {
        $di = $this->app->getDi();
        foreach ($this->bootstrappers as $class) {
            $di->get($class)->bootstrap();
        }
    }
}
