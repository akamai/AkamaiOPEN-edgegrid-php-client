<?php
/**
 * Akamai {OPEN} EdgeGrid Auth for PHP
 *
 * Akamai\Open\EdgeGrid\Client wraps GuzzleHttp\Client
 * providing request authentication/signing for Akamai
 * {OPEN} APIs.
 *
 * This client works _identically_ to GuzzleHttp\Client
 *
 * However, if you try to call an Akamai {OPEN} API you *must*
 * first call {@see Akamai\Open\EdgeGrid\Client->setAuth()}.
 *
 * @author Davey Shafik <dshafik@akamai.com>
 * @copyright Copyright 2015 Akamai Technologies, Inc. All rights reserved.
 * @license Apache 2.0
 * @link https://github.com/akamai-open/edgegrid-auth-php
 * @link https://developer.akamai.com
 * @link https://developer.akamai.com/introduction/Client_Auth.html
 */
namespace Akamai\Open\EdgeGrid;

use Akamai\Open\EdgeGrid\Authentication;
use Akamai\Open\EdgeGrid\Authentication\Nonce;
use Akamai\Open\EdgeGrid\Authentication\Timestamp;
use Akamai\Open\EdgeGrid\Client\OptionsHandler;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * Akamai {OPEN} EdgeGrid Client for PHP
 *
 * Akamai {OPEN} EdgeGrid Client for PHP. Based on
 * [Guzzle](http://guzzlephp.org).
 *
 * @package Akamai {OPEN} EdgeGrid Client
 */
class Client implements \GuzzleHttp\ClientInterface
{
    /**
     * @const int Default Timeout in seconds
     */
    const DEFAULT_REQUEST_TIMEOUT = 10;

    /**
     * @var \GuzzleHttp\Client Proxied GuzzleHttp\Client
     */
    protected $guzzle;

    /**
     * @var array Authentication credentials
     */
    protected $auth = [];

    /**
     * @var array Request query string
     */
    protected $query = [];
    
    /**
     * @var string Request body
     */
    protected $body = '';

    /**
     * @var array Request headers
     */
    protected $headers = [];

    /**
     * @var Client\OptionsHandler
     */
    protected $optionsHandler;

    /**
     * @var \Akamai\Open\EdgeGrid\Authentication
     */
    protected $authentication;

    /**
     * @var bool Whether verbose mode is on
     */
    protected $verbose = false;

    /**
     * @var bool Whether debugging is on
     */
    protected $debug = false;

    /**
     * @var bool Whether to override the static verbose setting
     */
    protected $verboseOverride = false;

    /**
     * @var bool Whether to override the static debug setting
     */
    protected $debugOverride = false;

    /**
     * @var bool Whether verbose mode is on
     */
    protected static $staticVerbose = false;

    /**
     * @var bool Whether debug mode is on
     */
    protected static $staticDebug = false;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected static $logger;

    /**
     * @var array An array of requests
     */
    protected $requests = [];

    /**
     * @var mixed History middleware to track requests
     * @see \GuzzleHttp\Middleware::history()
     */
    protected $history = false;

    /**
     * @var \GuzzleHttp\HandlerStack The handler stack for middleware
     */
    protected $handlerStack = false;
    
    /**
     * \GuzzleHttp\Client-compatible constructor
     *
     * @param array $options Options array
     * @param OptionsHandler|null $optionsHandler
     * @param Authentication|null $authentication
     */
    public function __construct(
        $options = [],
        OptionsHandler $optionsHandler = null,
        Authentication $authentication = null
    ) {
        $this->authentication = $authentication;
        if ($authentication === null) {
            $this->authentication = new Authentication();
        }

        $this->optionsHandler = $optionsHandler;
        if ($optionsHandler === null) {
            $this->optionsHandler = new OptionsHandler($this->authentication);
        }

        $this->optionsHandler->setAuthentication($this->authentication);

        $options = $this->handleOptions($options);

        $this->guzzle = new \GuzzleHttp\Client($options);
    }

    /**
     * Proxy calls smartly to the \GuzzleHttp\Client
     *
     * @param string $method
     * @param array $args
     * @return mixed
     * @throws \Exception
     */
    public function __call($method, $args)
    {
        // The only method that isn't a request-type method is getConfig
        // Don't create the auth header in that case
        if ($method != 'getConfig') {
            list($path, $options, $httpMethod) = $this->handleArgs($method, $args);

            $this->optionsHandler->setPath($path)
                ->setOptions($options);
            
            $this->authentication->setHttpMethod($httpMethod)
                ->setHost($this->optionsHandler->getHost())
                ->setPath($path);

            if (isset($options['timestamp'])) {
                $this->authentication->setTimestamp($options['timestamp']);
            } elseif (!$this->guzzle->getConfig('timestamp')) {
                $this->authentication->setTimestamp();
            }

            if (isset($options['nonce'])) {
                $this->authentication->setNonce($options['nonce']);
            }

            $options = $this->optionsHandler->getOptions();

            if ($handler = $this->getHandlerOption($options)) {
                $options['handler'] = $handler;
            }

            $options['debug'] = $this->getDebugOption($options);

            $args = $this->normalizeArgs($args, $path, $options, $method);
        }
        
        try {
            $return = call_user_func_array([$this->guzzle, $method], $args);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $this->log($e);
            throw $e;
        } finally {
            $this->cleanup();
            
            if (isset($httpMethod)) {
                $lastRequest = end($this->requests);
                $this->log($lastRequest);
                $this->verbose($lastRequest);
            }
        }
        
        return $return;
    }

    /**
     * Factory method to create a client using credentials from `.edgerc`
     *
     * Automatically checks your HOME directory, and the current working
     * directory for credentials, if no path is supplied.
     *
     * @param string $section Credential section to use
     * @param string $path Path to .edgerc credentials file
     * @param array $options Options to pass to the constructor/guzzle
     * @return \Akamai\Open\EdgeGrid\Client
     */
    public static function createFromEdgeRcFile($section = 'default', $path = null, $options = [])
    {
        $auth = Authentication::createFromEdgeRcFile($section, $path);
        
        if ($host = $auth->getHost()) {
            $options['base_uri'] = 'https://' .$host;
        }
        
        $client = new static($options, null, $auth);
        return $client;
    }

    /**
     * Set Akamai {OPEN} Authentication Credentials
     *
     * @param string $client_token
     * @param string $client_secret
     * @param string $access_token
     * @return $this
     */
    public function setAuth($client_token, $client_secret, $access_token)
    {
        $this->authentication->setAuth($client_token, $client_secret, $access_token);
        
        return $this;
    }

    /**
     * Specify the headers to include when signing the request
     *
     * This is specified by the API, currently no APIs use this
     * feature.
     *
     * @param array $headers
     * @return $this
     */
    public function setHeadersToSign(array $headers)
    {
        $this->authentication->setHeadersToSign($headers);

        return $this;
    }

    /**
     * Set the max body size
     *
     * @param int $max_body_size
     * @return $this
     */
    public function setMaxBodySize($max_body_size)
    {
        $this->authentication->setMaxBodySize($max_body_size);

        return $this;
    }

    /**
     * Set Request Host
     *
     * @param string $host
     * @return $this
     */
    public function setHost($host)
    {
        $this->optionsHandler->setHost($host);
        
        return $this;
    }

    /**
     * Set the HTTP request timeout
     *
     * @param int $timeout_in_seconds
     * @return $this
     */
    public function setTimeout($timeout_in_seconds)
    {
        $this->optionsHandler->setTimeout($timeout_in_seconds);

        return $this;
    }

    /**
     * Print formatted JSON responses to STDOUT
     *
     * @param boolean $enable
     * @return $this
     */
    public function setInstanceVerbose($enable)
    {
        $this->verboseOverride = true;
        $this->verbose = $enable;
        return $this;
    }

    /**
     * Print HTTP requests/responses to STDOUT
     *
     * @param boolean $enable
     * @return $this
     */
    public function setInstanceDebug($enable)
    {
        $this->debugOverride = true;
        $this->debug = $enable;
        return $this;
    }

    /**
     * Set a PSR-3 compatible logger (or use monolog by default)
     *
     * @param \Psr\Log\LoggerInterface $logger
     * @return void
     */
    public static function setLogger(
        \Psr\Log\LoggerInterface $logger = null,
        \Monolog\Handler\HandlerInterface $handler = null,
        \Monolog\Formatter\FormatterInterface $formatter = null
    ) {
        static::$logger = $logger;
        if (static::$logger === null) {
            static::$logger = new \Monolog\Logger(self::CLASS . ' Log');
        }

        if ($handler !== null) {
            static::$logger->pushHandler($handler);
        }

        if ($formatter !== null) {
            foreach (static::$logger->getHandlers() as $handler) {
                $handler->setFormatter($formatter);
            }
        }
    }

    /**
     * Add logger using a given filename/format
     *
     * @param string $filename
     * @param string $format
     */
    public static function setSimpleLog($filename, $format = "%message%\n")
    {
        $handler = new \Monolog\Handler\StreamHandler($filename);
        $handler->setFormatter(new \Monolog\Formatter\LineFormatter($format));

        if (!static::$logger) {
            self::setLogger(null, $handler);
            return;
        }

        static::$logger->pushHandler($handler);
    }

    /**
     * Print formatted JSON responses to STDOUT
     *
     * @param bool $enable
     */
    public static function setVerbose($enable)
    {
        self::$staticVerbose = $enable;
    }

    /**
     * Print HTTP requests/responses to STDOUT
     *
     * @param bool $enable
     */
    public static function setDebug($enable)
    {
        self::$staticDebug = $enable;
    }

    /**
     * Get handler option
     *
     * @param array $options Guzzle options
     * @return HandlerStack|bool
     */
    protected function getHandlerOption($options)
    {
        if ($this->isVerbose() || $this->isLogging()) {
            if (!$this->requests || !$this->history) {
                $this->requests = [];
                $this->history = Middleware::history($this->requests);
            }

            $handler = $this->handlerStack;

            // Is the user passing an handler in for this request?
            if (isset($options['handler'])) {
                $handler = $options['handler'];
            } elseif ($configHandler = $this->guzzle->getConfig('handler')) {
                $handler = $configHandler;
            } elseif (!$this->handlerStack) {
                $handler = HandlerStack::create();
            }

            $this->handlerStack = $handler;
            $handler->push($this->history);

            return $handler;
        }

        return false;
    }

    /**
     * Log something
     *
     * @param \Exception|array $what
     * @return void
     */
    protected function log($what)
    {
        if (!static::$logger) {
            return;
        }

        if ($what instanceof \GuzzleHttp\Exception\RequestException) {
            $what = ['request' => $what->getRequest(), 'response' => $what->getResponse()];
        }
        
        if (is_array($what) && isset($what['request'])) {
            $msg = [];
            $statusCode = false;
            
            if ($what['request'] instanceof RequestInterface) {
                $msg[] = $what['request']->getMethod();
                $msg[] = $what['request']->getUri()->getPath();
            }
            
            if (isset($what['response']) && $what['response'] instanceof ResponseInterface) {
                $statusCode = $what['response']->getStatusCode();
                ;
                $header = $what['response']->getHeader('Content-Type');
                    
                $msg[] = $statusCode;
                $msg[] = array_shift($header);
            }
            
            $msg = implode(" ", $msg);

            if ($statusCode) {
                $type = substr($statusCode, 0, 1);
                if (in_array($type, [4, 5])) {
                    static::$logger->error($msg);
                    return;
                }

                static::$logger->info($msg);
                return;
            }
            
            static::$logger->warning($msg);
        }
        
        return;
    }
    
    public function isLogging()
    {
        return static::$logger instanceof \Psr\Log\LoggerInterface;
    }
    
    /**
     * Output JSON when verbose mode is turned on
     *
     * @param \GuzzleHttp\Psr7\Request $lastRequest The last request
     * @see \Akamai\Open\EdgeGrid\Client::setInstanceVerbose
     */
    protected function verbose($lastRequest)
    {
        if (!$this->isVerbose()) {
            return;
        }
        
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
        
        echo "{$colors['cyan']}===> [VERBOSE] Response: \n";
        if (isset($lastRequest['response']) && $lastRequest['response'] instanceof ResponseInterface) {
            $body = trim($lastRequest['response']->getBody());
            $result = json_decode($body);
            if ($result !== null) {
                $response = json_encode($result, JSON_PRETTY_PRINT);
            } else {
                $response = $body;
            }
            echo "{$colors['yellow']}" . $response;
        } else {
            echo "{$colors['red']}No response returned";
        }
        echo "{$colors['reset']}\n";
    }
    
    protected function isVerbose()
    {
        if (($this->verboseOverride && !$this->verbose) || (!($this->verboseOverride) && !static::$staticVerbose)) {
            return false;
        }
        
        return true;
    }

    /**
     * Sort out path/options/http method args
     *
     * Regardless of whether a specific HTTP
     * method was called, or the generic request()
     * this will return the path/options/http method
     *
     * @param string $method Original method called
     * @param array $args Original __call() arguments
     * @return array
     */
    protected function handleArgs($method, $args)
    {
        $options = [];
        $path = '/';
        $httpMethod = strtolower(str_replace('async', '', $method));

        if ($httpMethod != 'request' && $httpMethod != 'send') {
            $path = $args[0];

            if (isset($args[1])) {
                $options = array_merge($this->guzzle->getConfig(), $args[1]);
            }
        }

        if ($httpMethod == 'send') {
            // PSR-7
            /**
             * @todo Add the request headers/body/auth to the PSR-7 Request
             */
            throw new \RuntimeException("Not Implemented");
        }

        if ($httpMethod == 'request') {
            $httpMethod = $args[0];
            $path = $args[1];

            if (isset($args[2])) {
                $options = array_merge($this->guzzle->getConfig(), $args[2]);
            }
        }
        
        if ((!isset($options['query']) || empty($options['query'])) && $query = parse_url($path, PHP_URL_QUERY)) {
            parse_str($query, $options['query']);
        }
        
        if ($url = parse_url($path)) {
            if (isset($url['host'])) {
                $options['base_uri'] = $url['scheme'] . '://' . $url['host'];
            }
        }
        
        $path = $this->normalizePath($path);

        return [$path, $options, $httpMethod];
    }

    /**
     * Normalize __call() arguments
     *
     * @param array $args Arguments passed to __call()
     * @param string $path Request Path
     * @param array $options Guzzle options
     * @param string $method original method called
     * @return array
     */
    protected function normalizeArgs($args, $path, $options, $method)
    {
        $httpMethod = strtolower(str_replace('async', '', $method));

        if ($httpMethod != 'request' && $httpMethod != 'send') { // not ->request() or ->send()
            $args[0] = $path;
            $args[1] = $options;
        }
        
        if ($httpMethod == 'send') {
            throw new \RuntimeException("Not implemented");
        }
        
        if ($httpMethod == 'request') {
            $args[0] = $method;
            $args[1] = $path;
            $args[2] = $options;
        }
        
        return $args;
    }

    /**
     * Normalize path
     *
     * This returns just the path part of the URL.
     * Most importantly, it removes any query args
     * as these are handler by {@see Client->getQueryOption()}
     *
     * @param string $path Path to normalize
     * @return string
     */
    protected function normalizePath($path)
    {
        return parse_url($path, PHP_URL_PATH);
    }

    /**
     * Cleanup for a new request
     *
     * @return void
     */
    protected function cleanup()
    {
        $this->query = [];
        $this->body = '';
        $this->headers = [];
    }

    /**
     * Handle incoming options
     *
     * @param array $options
     * @return mixed
     */
    protected function handleOptions($options)
    {
        if (isset($options['timestamp']) && $options['timestamp'] instanceof Timestamp) {
            $this->authentication->setTimestamp($options['timestamp']);
        }

        if (isset($options['nonce']) && $options['nonce'] instanceof Nonce) {
            $this->authentication->setNonce($options['nonce']);
        }

        if (!isset($options['timeout'])) {
            $options['timeout'] = $this->optionsHandler->getTimeout();
        } else {
            $this->optionsHandler->setTimeout($options['timeout']);
        }
        
        if (isset($options['debug'])) {
            $this->setInstanceDebug(true);
        }

        if (isset($options['base_uri'])) {
            $this->optionsHandler->setHost($options['base_uri']);

            if (strpos($options['base_uri'], '://') === false) {
                $options['base_uri'] = 'https://' . $options['base_uri'];
                return $options;
            }
            return $options;
        }
        return $options;
    }

    /**
     * Handle debug option
     *
     * @return bool
     */
    protected function getDebugOption($options)
    {
        if (isset($options['debug'])) {
            return $options['debug'];
        }
        
        if (($this->debugOverride && $this->debug) || (!$this->debugOverride && static::$staticDebug)) {
            return true;
        }
        
        return false;
    }

    /**
     * Send an HTTP request.
     *
     * @param RequestInterface $request Request to send
     * @param array $options Request options to apply to the given
     *                                  request and to the transfer.
     *
     * @return ResponseInterface
     * @throws GuzzleException
     * @codeCoverageIgnore
     */
    public function send(RequestInterface $request, array $options = [])
    {
        return $this->__call(__FUNCTION__, [$request, $options]);
    }

    /**
     * Asynchronously send an HTTP request.
     *
     * @param RequestInterface $request Request to send
     * @param array $options Request options to apply to the given
     *                                  request and to the transfer.
     *
     * @return PromiseInterface
     * @codeCoverageIgnore
     */
    public function sendAsync(RequestInterface $request, array $options = [])
    {
        return $this->__call(__FUNCTION__, [$request, $options]);
    }

    /**
     * Create and send an HTTP request.
     *
     * Use an absolute path to override the base path of the client, or a
     * relative path to append to the base path of the client. The URL can
     * contain the query string as well.
     *
     * @param string $method HTTP method
     * @param string|UriInterface $uri URI object or string.
     * @param array $options Request options to apply.
     *
     * @return ResponseInterface
     * @throws GuzzleException
     * @codeCoverageIgnore
     */
    public function request($method, $uri, array $options = [])
    {
        return $this->__call(__FUNCTION__, [$method, $uri, $options]);
    }

    /**
     * Create and send an asynchronous HTTP request.
     *
     * Use an absolute path to override the base path of the client, or a
     * relative path to append to the base path of the client. The URL can
     * contain the query string as well. Use an array to provide a URL
     * template and additional variables to use in the URL template expansion.
     *
     * @param string $method HTTP method
     * @param string|UriInterface $uri URI object or string.
     * @param array $options Request options to apply.
     *
     * @return PromiseInterface
     * @codeCoverageIgnore
     */
    public function requestAsync($method, $uri, array $options = [])
    {
        return $this->__call(__FUNCTION__, [$method, $uri, $options]);
    }

    /**
     * Get a client configuration option.
     *
     * These options include default request options of the client, a "handler"
     * (if utilized by the concrete client), and a "base_uri" if utilized by
     * the concrete client.
     *
     * @param string|null $option The config option to retrieve.
     *
     * @return mixed
     * @codeCoverageIgnore
     */
    public function getConfig($option = null)
    {
        return $this->__call(__FUNCTION__, [$option]);
    }
}
