<?php
/*
 * PHP version 5.5
 *
 * @copyright Copyright (c) 2012-2017 EELLY Inc. (http://www.eelly.com)
 * @link      http://www.eelly.com
 * @license   衣联网版权所有
 */

namespace Swallow\Mvc\Collection;

use Phalcon\Di;
use Swallow\Core\Conf;

/**
 * @author    hehui<hehui@eelly.net>
 *
 * @since     2016年10月3日
 *
 * @version   1.0
 */
class Manager extends \Phalcon\Mvc\Collection\Manager
{
    /**
     * @author hehui<hehui@eelly.net>
     *
     * @since  2017年4月6日
     */
    public function __construct()
    {
        $di = Di::getDefault();
        foreach (Conf::get('mongodb') as $key => $value) {
            $di->setShared('mongo_'.$key, [
                'className' => \MongoDB\Client::class,
                'arguments' => [
                    [
                        'type' => 'parameter',
                        'value' => $value,
                    ],
                    [
                        'type' => 'parameter',
                        'value' => [
                            'readPreference' => 'secondaryPreferred',
                        ],
                    ],
                ],
            ]);
        }
    }
}
