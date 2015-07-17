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
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use GuzzleHttp\Middleware;

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
            array $options
        ) use ($handler) {
            if ($request->getUri()->getScheme() !== 'https' ||
                strpos($request->getUri()->getHost(), 'akamaiapis.net') === false) {
                return $handler($request, $options);
            }

            if (!$this->signer) {
                throw new \Exception("You must call setSigner before trying to sign a request");
            }
            
            $this->signer->setHttpMethod($request->getMethod())
                ->setHost($request->getUri()->getHost())
                ->setPath($request->getUri()->getPath())
                ->setQuery($request->getUri()->getQuery())
                ->setHeaders($request->getHeaders())
                ->setBody($request->getBody()->getContents());

            $request = $request->withHeader('Authorization', $this->signer->createAuthHeader());
            
            return $handler($request, $options);
        };
    }
    
    public function __call($method, $args)
    {
        return call_user_func_array([$this->signer, $method], $args);
    }
    
    public static function createFromEdgeRcFile($section = "default", $file = null)
    {
        $signer = Signer::createFromEdgeRcFile($section, $file);
        $auth = new static();
        $auth->setSigner($signer);
        
        return $auth;
    }
}
