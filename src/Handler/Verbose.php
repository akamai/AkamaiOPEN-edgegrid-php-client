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

/**
 * Verbose Response Guzzle Middleware Handler
 *
 * @package Akamai {OPEN} EdgeGrid Auth
 */
class Verbose
{
    protected $fp;
    
    public function __construct($resource = null)
    {
        if (!is_resource($resource) && $resource !== null) {
            $fp = @fopen($fp, 'a+');
            if (!$fp) {
                throw new \Exception("Unable to use resource: " .$resource);
            }
        }
        
        if ($resource === null) {
            $fp = fopen('php://output', 'a');
        }
        
        $this->fp = $fp;
    }
    
    /**
     * Handle the request/response
     *
     * @param callable $handler
     * @return \Closure
     */
    public function __invoke(callable $handler)
    {
        $colors = [
            'red' => "",
            'yellow' => "",
            'cyan' => "",
            'reset' => "",
        ];

        if (PHP_SAPI == 'cli') {
            $colors = [
                'red' => "\x1b[31;01m",
                'yellow' => "\x1b[33;01m",
                'cyan' => "\x1b[36;01m",
                'reset' => "\x1b[39;49;00m",
            ];
        }
        
        return function (
            \Psr\Http\Message\RequestInterface $request,
            array $config
        ) use (
            $handler,
            $colors
) {
            return $handler($request, $config)->then(
                function (\Psr\Http\Message\ResponseInterface $response) use ($colors) {
                    $statusCode = $response->getStatusCode();
                    if ($statusCode > 299 && $statusCode < 400) {
                        fputs($this->fp, "{$colors['yellow']}===> [VERBOSE] Redirected: ");
                        fputs($this->fp, $response->getHeader('Location')[0] . "\n");
                    } else {
                        if ($statusCode > 399 && $statusCode < 600) {
                            fputs($this->fp, "{$colors['red']}===> [ERROR] An error occurred: \n");
                        } else {
                            fputs($this->fp, "{$colors['cyan']}===> [VERBOSE] Response: \n");
                        }
                        $body = trim($response->getBody());
                        $result = json_decode($body);
                        if ($result !== null) {
                            $responseBody = json_encode($result, JSON_PRETTY_PRINT);
                        } else {
                            $responseBody = (!empty(trim($body))) ? $body : "No response body returned";
                        }
                        fputs($this->fp, "{$colors['yellow']}" . $responseBody);
                    }
                    fputs($this->fp, "{$colors['reset']}\n");
                    
                    return $response;
                },
                function (\Exception $reason) use ($colors) {
                    fputs($this->fp, "{$colors['red']}===> [ERROR] An error occurred: \n");
                    fputs($this->fp, "{$colors['yellow']}");

                    $code = $reason->getCode();
                    if (!empty($code)) {
                        $code .= ': ';
                    }

                    $message = $reason->getMessage();

                    fputs($this->fp, ((!empty($code)) ? $code : "") . $message);

                    $response = $reason instanceof \GuzzleHttp\Exception\RequestException
                        ? $reason->getResponse()
                        : false;

                    if ($response instanceof \Psr\Http\Message\ResponseInterface) {
                        $body = $response->getBody()->getContents();
                        if (!empty($body)) {
                            fputs($this->fp, "\n{$colors['yellow']}" . $body);
                        }
                    }

                    fputs($this->fp, "{$colors['reset']}\n");
                    
                    return new \GuzzleHttp\Promise\RejectedPromise($reason);
                }
            );
        };
    }
}
