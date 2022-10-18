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

use Akamai\Open\EdgeGrid\Exception\HandlerException\IOException;

/**
 * Debug Response Guzzle Middleware Handler
 *
 * @package Akamai\Open\EdgeGrid\Client
 */
class Debug
{
    protected static $messages = [
        403 => [
            "This indicates a problem with authorization.\n",
            "Please ensure that the credentials you created for this script\n",
            "have the necessary permissions in the Luna portal.\n",
        ],
        400 => [
            'This indicates a problem with authentication or headers.',
            'Please ensure that the .edgerc file is formatted correctly.',
            'If you still have issues, please use gen_edgerc.php to generate the credentials',
        ],
        401 => 400,
        404 => [
            "This means that the page does not exist as requested.\n",
            "Please ensure that the URL you're calling is correctly formatted\n",
            "or look at other examples to make sure yours matches.\n",
        ]
    ];

    protected $fp;

    /**
     * Debug constructor.
     *
     * This method accepts a stream resource or a valid stream URL
     * (including file paths).  If none is passed in stderr is used.
     *
     * @param resource|null $resource
     * @throws \Akamai\Open\EdgeGrid\Exception\HandlerException\IOException
     */
    public function __construct($resource = null)
    {
        $fp = $resource;

        if (!is_resource($fp) && $fp !== null) {
            $fp = @fopen($fp, 'ab+');
            if (!$fp) {
                throw new IOException('Unable to use resource: ' . (string) $resource);
            }
        }

        if ($fp === null) {
            $fp = fopen('php://stderr', 'ab');
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
            'red' => '',
            'yellow' => '',
            'cyan' => '',
            'reset' => '',
        ];

        if (PHP_SAPI === 'cli') {
            $colors = [
                'red' => "\x1b[31;01m",
                'yellow' => "\x1b[33;01m",
                'cyan' => "\x1b[36;01m",
                'reset' => "\x1b[39;49;00m",
            ];
        }

        return function (\Psr\Http\Message\RequestInterface $request, array $config) use ($handler, $colors) {
            return $handler($request, $config)->then(
                function (\Psr\Http\Message\ResponseInterface $response) use ($colors, $request) {
                    $statusCode = $response->getStatusCode();
                    if ($statusCode > 399 && $statusCode < 600) {
                        $body = trim($response->getBody());
                        $result = json_decode($body);
                        if ($result !== null) {
                            if (isset($result->detail)) {
                                $detail = $result->detail;
                            } else {
                                $detail = json_encode($result, JSON_PRETTY_PRINT);
                            }
                        } else {
                            $detail = (!empty(trim($body))) ? $body : 'No response body returned';
                        }

                        $out = [];
                        $out[] = "{$colors['red']}===> [ERROR] Call to %s failed with a %s result";

                        if (isset(self::$messages[$statusCode])) {
                            $message = self::$messages[$statusCode];
                            if (is_int($message) && isset(self::$messages[$message])) {
                                $message = self::$messages[$message];
                            }

                            foreach ($message as $line) {
                                $out[] = '===> [ERROR] ' . $line;
                            }
                        }

                        $out[] = '===> [ERROR] Problem details:';
                        $out[] = $detail;

                        $out = sprintf(
                            implode("\n", $out),
                            $request->getUri()->getPath(),
                            $response->getStatusCode() . ' ' . $response->getReasonPhrase(),
                            $detail
                        );

                        fwrite($this->fp, $out);
                        fwrite($this->fp, "{$colors['reset']}\n");
                    }

                    return $response;
                },
                function (\Exception $reason) {
                    return new \GuzzleHttp\Promise\RejectedPromise($reason);
                }
            );
        };
    }
}
