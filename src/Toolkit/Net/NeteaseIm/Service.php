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
        $callMethod = array_slice(debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS), 1, 1);
        $reader = (new \Phalcon\Annotations\Adapter\Memory())->getMethod($callMethod[0]['class'], $callMethod[0]['function']);
        if (!$reader->has('uri')){
            throw new \ErrorException($callMethod[0]['function'] . ' not found uri annotation');
        };
        $annotation = $reader->get('uri');
        $uri = $annotation->getArgument(0);
        $response = $this->neteaseIm->request($uri, $args);
        if ('200' != $response['code']){
            $errorMessage = sprintf('[statusCode] %s,[errorInfo] %s [requestUri] %s [requestArgs] %s',
                $response['code'],
                $response['desc'],
                $uri,
                json_encode($args)
            );
            throw new ErrorException($errorMessage);
        }

        return $response;
    }
}