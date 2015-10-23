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

use Akamai\Open\EdgeGrid\Exception\HandlerException\IOException;

/**
 * Verbose Response Guzzle Middleware Handler
 *
 * @package Akamai {OPEN} EdgeGrid Auth
 */
class Verbose
{
    protected $outputStream;

    protected $errorStream;

    public function __construct($outputStream = null, $errorStream = null)
    {
        $errorStreamException = null;
        if (!is_resource($errorStream) && $errorStream !== null) {
            $fp = @fopen($errorStream, 'a+');
            if (!$fp) {
                $errorStreamException = new IOException("Unable to use error stream: " . (string) $errorStream);
            }
            $errorStream = $fp;
        }

        if (!is_resource($outputStream) && $outputStream !== null) {
            $fp = @fopen($outputStream, 'a+');
            if (!$fp) {
                throw new IOException("Unable to use output stream: " . (string) $outputStream);
            }
            $outputStream = $fp;
        }

        if ($errorStreamException instanceof \Exception) {
            throw $errorStreamException;
        }

        if ($outputStream !== null && $errorStream === null) {
            $errorStream = $outputStream;
        }

        if ($outputStream === null && $errorStream === null) {
            $errorStream = fopen('php://stderr', 'a');
        }

        if ($outputStream === null) {
            $outputStream = fopen('php://output', 'a');
        }

        $this->outputStream = $outputStream;
        $this->errorStream = $errorStream;
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
            fputs($this->outputStream, "{$colors['cyan']}===> [VERBOSE] Request: \n");
            fputs($this->outputStream, "{$colors['yellow']}" . $this->getBody($request));
            fputs($this->outputStream, "{$colors['reset']}\n");

            return $handler($request, $config)->then(
                function (\Psr\Http\Message\ResponseInterface $response) use ($colors) {
                    $statusCode = $response->getStatusCode();
                    if ($statusCode > 299 && $statusCode < 400) {
                        fputs($this->outputStream, "{$colors['cyan']}===> [VERBOSE] Redirected: ");
                        fputs($this->outputStream, $response->getHeader('Location')[0]);
                        fputs($this->outputStream, "{$colors['reset']}\n");
                    } else {
                        $responseBody = $this->getBody($response);

                        if ($statusCode > 399 && $statusCode < 600) {
                            fputs($this->errorStream, "{$colors['red']}===> [ERROR] An error occurred: \n");
                            fputs($this->errorStream, "{$colors['yellow']}" . $responseBody);
                            fputs($this->errorStream, "{$colors['reset']}\n");
                        } else {
                            fputs($this->outputStream, "{$colors['cyan']}===> [VERBOSE] Response: \n");
                            fputs($this->outputStream, "{$colors['yellow']}" . $responseBody);
                            fputs($this->outputStream, "{$colors['reset']}\n");
                        }
                    }

                    return $response;
                },
                function (\Exception $reason) use ($colors) {
                    fputs($this->outputStream, "{$colors['red']}===> [ERROR] An error occurred: \n");
                    fputs($this->outputStream, "{$colors['yellow']}");

                    $code = $reason->getCode();
                    if (!empty($code)) {
                        $code .= ': ';
                    }

                    $message = $reason->getMessage();

                    fputs($this->outputStream, ((!empty($code)) ? $code : "") . $message);

                    $response = $reason instanceof \GuzzleHttp\Exception\RequestException
                        ? $reason->getResponse()
                        : false;

                    if ($response instanceof \Psr\Http\Message\ResponseInterface) {
                        $body = $response->getBody()->getContents();
                        if (!empty($body)) {
                            fputs($this->outputStream, "\n{$colors['yellow']}" . $body);
                        }
                    }

                    fputs($this->outputStream, "{$colors['reset']}\n");

                    return new \GuzzleHttp\Promise\RejectedPromise($reason);
                }
            );
        };
    }

    protected function getBody(\Psr\Http\Message\MessageInterface $message)
    {
        $body = trim($message->getBody());

        if ($message->getBody()->getSize() == 0 || empty($body)) {
            if ($message instanceof \Psr\Http\Message\ResponseInterface) {
                return "No response body returned";
            }
            return "No request body sent";
        }
        $result = json_decode($body);
        if ($result !== null) {
            return json_encode($result, JSON_PRETTY_PRINT);
        }

        return $body;
    }
}
