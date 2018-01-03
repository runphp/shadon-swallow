<?php
/*
 * PHP version 5.5
 *
 * @copyright Copyright (c) 2012-2017 EELLY Inc. (http://www.eelly.com)
 * @link      http://www.eelly.com
 * @license   衣联网版权所有
 */

namespace Swallow\ThirdParty\Easemob\Service;

use Swallow\ThirdParty\Easemob\Manager;

/**
 * 抽象的服务
 *
 * @author    hehui<hehui@eelly.net>
 *
 * @since     2016年10月1日
 *
 * @version   1.0
 */
abstract class AbstractService implements ServiceInterface
{
    private $manager;

    public function setManager(Manager $manager)
    {
        $this->manager = $manager;
    }

    public function getManager()
    {
        return $this->manager;
    }
}
