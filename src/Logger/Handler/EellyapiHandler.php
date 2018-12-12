<?php
/*
 * This file is part of eelly package.
 *
 * (c) eelly.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swallow\Logger\Handler;

use Eelly\SDK\Logger\Api\DingLogger;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

/**
 * Class EellyapiHandler.
 *
 * @author hehui<runphp@dingtalk.com>
 */
class EellyapiHandler extends AbstractProcessingHandler
{


    public function __construct($level = Logger::NOTICE, $bubble = true)
    {
        parent::__construct($level, $bubble);
    }

    /**
     * Writes the record down to the log of the implementing handler.
     *
     * @param array $record
     */
    protected function write(array $record)
    {
        register_shutdown_function(function ($record) {
            try {
                (new DingLogger())->monolog($record);
            } catch (\Throwable $e) {
                 // ...
            }
        }, $record);
    }
}
