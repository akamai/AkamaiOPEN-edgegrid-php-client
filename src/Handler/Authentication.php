<?php

/**
 * Akamai {OPEN} EdgeGrid Auth Client
 *
 * @author Davey Shafik <dshafik@akamai.com>
 * @copyright Copyright 2016 Akamai Technologies, Inc. All rights reserved.
 * @license Apache 2.0
 * @link https://github.com/akamai-open/AkamaiOPEN-edgegrid-php-client
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
 * @package Akamai\Open\EdgeGrid\Client
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

    /**
     * Inject signer object
     *
     * @param Signer|null $auth
     */
    public function setSigner(\Akamai\Open\EdgeGrid\Authentication $auth = null)
    {
        $this->signer = $auth;
        if ($this->signer === null) {
            $this->signer = new Signer();
        }
    }

    /**
     * Handler magic invoker
     *
     * @param callable $handler The next handler in the stack
     * @return \Closure
     * @throws \Akamai\Open\EdgeGrid\Exception\HandlerException
     */
    public function __invoke(callable $handler)
    {
        return function (
            RequestInterface $request,
            array $config
        ) use ($handler) {
            if (
                $request->getUri()->getScheme() !== 'https' ||
                strpos($request->getUri()->getHost(), 'akamaiapis.net') === false
            ) {
                return $handler($request, $config);
            }

            if (!$this->signer) {
                throw new HandlerException('Signer not set, make sure to call setSigner first');
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
     * @throws \Akamai\Open\EdgeGrid\Exception\HandlerException
     */
    public function __call($method, $args)
    {
        if ($this->signer === null) {
            throw new HandlerException('Signer not set, make sure to call setSigner first');
        }

        $return = call_user_func_array([$this->signer, $method], $args);
        if ($return == $this->signer) {
            return $this;
        }

        return $return;
    }

    /**
     * Create Handler using an .edgerc file
     *
     * Automatically create a valid authentication handler using
     * an .edgerc file
     *
     * @param string $section
     * @param null $file
     *
     * @return static
     */
    public static function createFromEdgeRcFile($section = 'default', $file = null)
    {
        $signer = Signer::createFromEdgeRcFile($section, $file);
        $auth = new static();
        $auth->setSigner($signer);

        return $auth;
    }
}
