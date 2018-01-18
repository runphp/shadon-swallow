<?php

namespace Swallow\Toolkit\Net\NeteaseIm;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;

class NeteaseIm
{
    protected static $instance = null;

    protected $appKey;

    protected $appSecret;

    protected $nonce;

    protected $baseUri = 'https://api.netease.im/nimserver/';

    protected $client = null;

    public static function getInstance(array $config)
    {
        if (!self::$instance instanceof self){
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    private function __construct(array $config)
    {
        if (empty($config)){
            throw new ErrorException('neteaseIm config cannot be empty');
        }

        $this->appKey = isset($config['appKey']) ? $config['appKey'] : '6d5ae52067f6a01c028fa5bc6dbf6fdf';
        $this->appSecret = isset($config['appSecret']) ? $config['appSecret'] : 'ea536df58c5a';
        $this->client = new Client([
            'base_uri' => $this->baseUri,
        ]);
    }

    private function setNonce()
    {
        $this->nonce = '';
        $str = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        for ($i = 0; $i < 30; $i++){
            $rand = mt_rand(0, (strlen($str) - 1));
            $this->nonce .= $str[$rand];
        }
    }

    public function request($uri, $args)
    {
        $this->setNonce();
        $curTime = time();
        $checkSum = sha1(sprintf('%s%s%s',
                $this->appSecret,
                $this->nonce,
                $curTime
            ));
        $headers = [
            'AppKey' => $this->appKey,
            'Nonce' => $this->nonce,
            'CurTime' => $curTime,
            'CheckSum' => $checkSum,
        ];
        $options = [
            'headers' => $headers,
            'form_params' => $args,
        ];
        /** @var Response $response */
        $response = $this->client->request('POST', $uri, $options);
        if ('200' != $response->getStatusCode()){
            $errorMessage = sprintf('[statusCode] %s,[reasonPhrase] %s,[errorInfo] %s',
                    $response->getStatusCode(),
                    $response->getReasonPhrase(),
                    $response->getBody()->getContents()
                );
            throw new \ErrorException($errorMessage);
        }

        return json_decode($response->getBody()->getContents(), true);
    }
}