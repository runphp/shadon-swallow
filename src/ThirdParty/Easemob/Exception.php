<?php
/*
 * PHP version 5.5
 *
 * @copyright Copyright (c) 2012-2017 EELLY Inc. (http://www.eelly.com)
 * @link      http://www.eelly.com
 * @license   衣联网版权所有
 */

namespace Swallow\ThirdParty\Easemob;

use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Swallow\Exception\SystemException;

/**
 * 环信服务端异常.
 *
 * @author    hehui<hehui@eelly.net>
 *
 * @since     2016年10月6日
 *
 * @version   1.0
 */
class Exception extends SystemException
{
    /**
     * http://docs.easemob.com/start/450errorcode/10restapierrorcode.
     *
     * @var array
     */
    private static $messages = [
        400 => '（错误请求）服务器不理解请求的语法。',
        401 => '（未授权）请求要求身份验证。对于需要token的接口，服务器可能返回此响应。',
        403 => '（禁止）服务器拒绝请求。',
        404 => '（未找到）服务器找不到请求的接口。',
        408 => '（请求超时）服务器等候请求时发生超时。',
        413 => '（请求体过大）请求体超过了5kb，拆成更小的请求体重试即可。',
        429 => '（服务不可用）请求接口超过调用频率限制，即接口被限流。',
        500 => '（服务器内部错误）服务器遇到错误，无法完成请求。',
        501 => '（尚未实施）服务器不具备完成请求的功能。例如，服务器无法识别请求方法时可能会返回此代码。',
        502 => '（错误网关）服务器作为网关或代理，从上游服务器收到无效响应。',
        503 => '（服务不可用）请求接口超过调用频率限制，即接口被限流。',
        504 => '（网关超时）服务器作为网关或代理，但是没有及时从上游服务器收到请求。',
    ];

    /**
     * Factory method to create a new exception with a normalized error message.
     *
     * @param RequestInterface  $request  Request
     * @param ResponseInterface $response Response received
     * @param \Exception        $previous Previous exception
     * @param array             $ctx      optional handler context
     *
     * @return self
     */
    public static function create(
        RequestInterface $request,
        ResponseInterface $response = null,
        \Exception $previous = null,
        array $ctx = []
    ) {
        $e = RequestException::create($request, $response, $previous);
        $message = $e->getMessage();
        if ($response instanceof ResponseInterface) {
            $statusCode = $response->getStatusCode();
            if (isset(self::$messages[$statusCode])) {
                $message .= ' [reason phrase 中文] ' . self::$messages[$statusCode];
            }
            $message .= ' [easemob error] ' . $response->getBody()->getContents();
        }
        return new self($message, $e->getCode(), $e);
    }
}
