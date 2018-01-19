<?php

declare(strict_types=1);

/*
 * This file is part of eelly package.
 *
 * (c) eelly.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
     * @var array
     */
    private $slowLogTimes = ['default' => 5];

    /**
     * @author hehui<hehui@eelly.net>
     *
     * @since  2017年4月6日
     */
    public function __construct()
    {
        $di = Di::getDefault();
        foreach (Conf::get('mongodb') as $key => $value) {
            if (is_array($value)) {
                $this->slowLogTimes[$key] = $value['slowLogTime'];
            }
            $di->setShared('mongo_'.$key, [
                'className' => \MongoDB\Client::class,
                'arguments' => [
                    [
                        'type'  => 'parameter',
                        'value' => is_array($value) ? $value['uri'] : $value,
                    ],
                    [
                        'type'  => 'parameter',
                        'value' => [
                            'readPreference' => 'secondaryPreferred',
                        ],
                    ],
                ],
            ]);
        }
    }

    /**
     * @param string $databaseName
     *
     * @return int|mixed
     */
    public function getSlowLogTime(string $databaseName)
    {
        return isset($this->slowLogTimes[$databaseName]) ? $this->slowLogTimes[$databaseName] : $this->slowLogTimes['default'];
    }
}
