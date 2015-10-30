<?php
/**
 * Akamai {OPEN} EdgeGrid Auth for PHP
 *
 * Provides Request Signing as per
 * {@see https://developer.akamai.com/introduction/Client_Auth.html}
 * as GuzzleHttp a Middleware Handlers
 *
 * @author Davey Shafik <dshafik@akamai.com>
 * @copyright Copyright 2015 Akamai Technologies, Inc. All rights reserved.
 * @license Apache 2.0
 * @link https://github.com/akamai-open/edgegrid-auth-php
 * @link https://developer.akamai.com
 * @link https://developer.akamai.com/introduction/Client_Auth.html
 */
namespace Akamai\Open\EdgeGrid\Handler;

use Akamai\Open\EdgeGrid\Authentication as Signer;
use Akamai\Open\EdgeGrid\Exception\HandlerException;
use Psr\Http\Message\RequestInterface;

/**
 * Akamai {OPEN} EdgeGrid Auth Guzzle Middleware Handler
 *
 * @package Akamai {OPEN} EdgeGrid Auth
 *
 * @method $this createAuthHeader()
 * @method $this setHttpMethod($method)
 * @method $this getHost()
 * @method $this setHost($host)
 * @method $this setConfig(array $config)
 * @method $this setQuery($query, $ensure_encoding = true)
 * @method $this setBody($body)
 * @method $this setHeaders(array $headers) *
 * @method $this setPath($path)
 * @method $this setTimestamp($timestamp = null)
 * @method $this setNonce($nonce = null)
 * @method $this setHeadersToSign($headers_to_sign)
 * @method $this setMaxBodySize($max_body_size)
 * @method $this setAuth($client_token, $client_secret, $access_token)
 */
class Authentication
{
    /**
     * @var \Akamai\Open\EdgeGrid\Authentication
     */
    protected $signer;

    public function setSigner(\Akamai\Open\EdgeGrid\Authentication $auth = null)
    {
        $this->signer = $auth;
        if ($this->signer === null) {
            $this->signer = new Signer();
        }
    }

    public function __invoke(callable $handler)
    {
        return function (
            RequestInterface $request,
            array $config
        ) use ($handler) {
            if ($request->getUri()->getScheme() !== 'https' ||
                strpos($request->getUri()->getHost(), 'akamaiapis.net') === false
            ) {
                return $handler($request, $config);
            }

            if (!$this->signer) {
                throw new HandlerException("Signer not set, make sure to call setSigner first");
            }

            $request->getBody()->rewind();

            $this->signer->setHttpMethod($request->getMethod())
                ->setHost($request->getUri()->getHost())
                ->setPath($request->getUri()->getPath())
                ->setQuery($request->getUri()->getQuery())
                ->setHeaders($request->getHeaders())
                ->setBody($request->getBody()->getContents());

            $request = $request->withHeader('Authorization', $this->signer->createAuthHeader());

            return $handler($request, $config);
        };
    }

    /**
     * Proxy to the underlying signer object
     *
     * @param $method
     * @param $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        if (!isset($this->signer)) {
            throw new HandlerException("Signer not set, make sure to call setSigner first");
        }

        $return = call_user_func_array([$this->signer, $method], $args);
        if ($return == $this->signer) {
            return $this;
        }

        return $return;
    }

    public static function createFromEdgeRcFile($section = "default", $file = null)
    {
        $signer = Signer::createFromEdgeRcFile($section, $file);
        $auth = new static();
        $auth->setSigner($signer);

        return $auth;
    }
}
