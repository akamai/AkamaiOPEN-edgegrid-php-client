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

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Middleware;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Akamai\Open\EdgeGrid\Client\OptionsHandler;
use Akamai\Open\EdgeGrid\Authentication;
use Akamai\Open\EdgeGrid\Authentication\Timestamp;
use Akamai\Open\EdgeGrid\Authentication\Nonce;

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

    protected $verbose = false;
    protected $debug = false;
    protected $verboseOverride = false;
    protected $debugOverride = false;
    protected static $staticVerbose = false;
    protected static $staticDebug = false;

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
            #$options = [];

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
        } finally {
            $this->cleanup();
            
            if (isset($httpMethod)) {
                $this->verbose();
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
        if ($path === null) {
            if (isset($_SERVER['HOME']) && file_exists($_SERVER['HOME'] . '/.edgerc')) {
                $path = $_SERVER['HOME'] . "/.edgerc";
            } elseif (file_exists('./.edgerc')) {
                $path = './.edgerc';
            }
        }
        
        $file = !$path ? false : realpath($path);
        if (!$file) {
            throw new \Exception("File \"$file\" does not exist!");
        }
        
        if (!is_readable($file)) {
            throw new \Exception("Unable to read .edgerc file!");
        }
        
        $ini = parse_ini_file($file, true, INI_SCANNER_RAW);
        if (!$ini[$section]) {
            throw new \Exception("Section \"$section\" does not exist!");
        }
        
        $client = new static(array_merge($options, ['base_uri' => 'https://' . $ini[$section]['host']]));
        $client->setAuth(
            $ini[$section]['client_token'],
            $ini[$section]['client_secret'],
            $ini[$section]['access_token']
        );
        if (isset($ini[$section]['max-size'])) {
            $client->setMaxBodySize($ini[$section]['max-size']);
        }

        return $client;
    }

    /**
     * Set Akamai {OPEN} Authentication Credentials
     *
     * @param $client_token
     * @param $client_secret
     * @param $access_token
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
     * @param $host
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
     * @param $timeout_in_seconds
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
     * @param $enable
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
     * @param $enable
     * @return $this
     */
    public function setInstanceDebug($enable)
    {
        $this->debugOverride = true;
        $this->debug = $enable;
        return $this;
    }

    /**
     * Print formatted JSON responses to STDOUT
     *
     * @param $enable
     */
    public static function setVerbose($enable)
    {
        self::$staticVerbose = $enable;
    }

    /**
     * Print HTTP requests/responses to STDOUT
     *
     * @param $enable
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
        if ($this->isVerbose()) {
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
     * Output JSON when verbose mode is turned on
     *
     * @param $requests Array of requests captured by {@see GuzzleHttp\MiddleWare::history()}
     * @see \Akamai\Open\EdgeGrid\Client::setInstanceVerbose
     */
    protected function verbose()
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
        
        $lastRequest = end($this->requests);
        echo "{$colors['cyan']}===> [VERBOSE] Response: \n";
        if ($lastRequest['response'] instanceof ResponseInterface) {
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
     * @param $options
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
     */
    public function getConfig($option = null)
    {
        return $this->__call(__FUNCTION__, [$option]);
    }
}
