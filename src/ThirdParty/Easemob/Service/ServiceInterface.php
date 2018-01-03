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

interface ServiceInterface
{
    const POST = 'POST';

    const GET = 'GET';

    const PUT = 'PUT';

    const DELETE = 'DELETE';

    public function setManager(Manager $manager);

    public function getManager();
}
