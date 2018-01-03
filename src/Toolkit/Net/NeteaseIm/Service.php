<?php

namespace Swallow\Toolkit\Net\NeteaseIm;

use Swallow\Core\Conf;
use Whoops\Exception\ErrorException;

class Service
{
    protected static $instance = [];

    protected $neteaseIm = null;

    /**
     *
     * @return static
     */
    public static function getInstance()
    {
        $calledClass = get_called_class();
        if (!isset(self::$instance[$calledClass])){
            $config = Conf::get('IM/inc/neteaseIm');
            self::$instance[$calledClass] = new static($config);
        }

        return self::$instance[$calledClass];
    }

    private function __construct(array $config)
    {
        if (empty($config)){
            throw new ErrorException('neteaseIm config cannot be empty');
        }

        $this->neteaseIm = NeteaseIm::getInstance($config);
    }

    protected function getResponse($args)
    {
        $response = $this->neteaseIm->request($args);
        if ('200' != $response['code']){
            $errorMessage = sprintf('[statusCode] %s,[errorInfo] %s',
                $response['code'],
                $response['desc']
                );
            throw new ErrorException($errorMessage);
        }

        return $response;
    }
}