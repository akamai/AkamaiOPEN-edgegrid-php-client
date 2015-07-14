<?php
/**
 * Akamai {OPEN} EdgeGrid Client for PHP
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

/**
 * Class Client
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
     * @var boolean Print JSON responses to STDOUT
     */
    protected static $verbose = false;

    /**
     * @var boolean Print HTTP request/responses to STDOUT
     */
    protected static $debug = false;

    /**
     * @var array An array of requests
     */
    protected static $requests = false;

    /**
     * @var mixed History middleware to track requests
     * @see \GuzzleHttp\Middleware::history()
     */
    protected static $history = false;

    /**
     * @var \GuzzleHttp\HandlerStack The handler stack for middleware
     */
    protected static $handlerStack = false;
    
    /**
     * @var \GuzzleHttp\Client Proxied GuzzleHttp\Client
     */
    protected $guzzle;

    /**
     * @var array Authentication credentials
     */
    protected $auth = [];

    /**
     * @var string Akamai {OPEN} API host
     */
    protected $host;

    /**
     * @var int Timeout in seconds
     */
    protected $timeout_in_seconds = self::DEFAULT_REQUEST_TIMEOUT;

    /**
     * @var int Maximum body size for signing
     */
    protected $max_body_size = 131072;

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
     * @var array A list of headers to be included in the signature
     */
    protected $headers_to_sign = [];

    /**
     * @var Client\Timestamp Request timestamp
     */
    protected $timestamp;

    /**
     * @var Client\Nonce Requent nonce
     */
    protected $nonce;

    /**
     * \GuzzleHttp\Client-compatible constructor
     *
     * @param array $options Options array
     * @param Client\Timestamp|null $timestamp inject a timestamp (for testing)
     * @param Client\Nonce|null $nonce inject a nonce (for testing)
     */
    public function __construct($options = [], Client\Timestamp $timestamp = null, Client\Nonce $nonce = null)
    {
        if (isset($options['base_uri']) && strpos($options['base_uri'], '://') === false) {
            $options['base_uri'] = 'https://' . $options['base_uri'];
        }
        
        if (!isset($options['timeout'])) {
            $options['timeout'] = $this->timeout_in_seconds;
        } else {
            $this->timeout_in_seconds = $options['timeout'];
        }
        
        $this->guzzle = new \GuzzleHttp\Client($options);
        
        if (isset($options['base_uri'])) {
            $this->setHost($options['base_uri']);
        }
        
        $this->timestamp = $timestamp;
        if ($timestamp === null) {
            $this->timestamp = new Client\Timestamp;
        }
        
        $this->nonce = $nonce;
        if ($nonce === null) {
            $this->nonce = new Client\Nonce();
        }
    }

    /**
     * Set Akamai EdgeGrid Authentication Tokens/Secret
     *
     * @param string $client_token
     * @param string $client_secret
     * @param string $access_token
     */
    public function setAuth($client_token, $client_secret, $access_token)
    {
        $this->auth = compact('client_token', 'client_secret', 'access_token');
    }

    /**
     * Set Akamai EdgeGrid API host
     *
     * @param $host
     */
    public function setHost($host)
    {
        if (strpos($host, '://') !== false) {
            $host = parse_url($host, PHP_URL_HOST);
        }
        
        if (substr($host, -1) == '/') {
            $host = substr($host, 0, -1);
        }
        
        $this->host = $host;
    }

    /**
     * Specify the headers to include when signing the request
     *
     * This is specified by the API, currently no APIs use this
     * feature.
     *
     * @param array $headers
     */
    public function setHeadersToSign(array $headers)
    {
        $this->headers_to_sign = $headers;
    }

    /**
     * Set the max body size
     *
     * @param int $max_body_size
     */
    public function setMaxBodySize($max_body_size)
    {
        $this->max_body_size = $max_body_size;
    }

    /**
     * Set the HTTP request timeout
     *
     * @param $timeout_in_seconds
     */
    public function setTimeout($timeout_in_seconds)
    {
        $this->timeout_in_seconds = $timeout_in_seconds;
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
            $options = [];
            
            if (!empty($this->auth)) {
                $httpMethod = str_replace('async', '', $method);

                if ($httpMethod != 'request') {
                    $path = &$args[0];

                    if (isset($args[1])) {
                        $options = &$args[1];
                    } else {
                        $args[1] = &$options;
                    }
                } elseif ($httpMethod == 'send') {
                    // PSR-7
                    /**
                     * @todo Add the request headers/body/auth to the PSR-7 Request
                     */
                    throw new \RuntimeException("Not Implemented");
                } else {
                    $httpMethod = $args[0];
                    $path = &$args[1];

                    if (isset($args[2])) {
                        $options = &$args[2];
                    } else {
                        $args[2] = &$options;
                    }
                }

                $this->query = isset($options['query']) ? $options['query'] : [];
                $this->body = isset($options['body']) ? $options['body'] : $this->body;
                $this->body = isset($options['form_params']) ? http_build_query($options['form_params']) : $this->body;
                $this->headers = isset($options['headers']) ? $options['headers'] : [];

                if (isset($options['base_uri'])) {
                    $this->setHost($options['base_uri']);
                } elseif (isset($this->host)) {
                    $options['base_uri'] = 'https://' . $this->host;
                } else {
                    throw new \Exception("No Host set");
                }

                if (isset($options['headers']['Host'])) {
                    $this->setHost($options['headers']['Host']);
                }

                if ($query = parse_url($path, PHP_URL_QUERY)) {
                    parse_str($query, $this->query);
                    $path = str_replace('?' . $query, '', $path);
                }

                if (strpos($options['base_uri'], 'https://') !== false &&
                    strpos($options['base_uri'], 'akamaiapis.net') !== false) {
                    $options['headers']['Authorization'] = $this->createAuthHeader($httpMethod, $path);
                }
            }
            
            if (self::$verbose) {
                self::$requests = [];
                self::$history = Middleware::history(self::$requests);
                
                if (!isset($options['handler'])) {
                    if ($handler = $this->guzzle->getConfig('handler')) {
                        $handler->push(self::$history);
                    } else {
                        self::$handlerStack = HandlerStack::create();
                        self::$handlerStack->push(self::$history);

                        $options['handler'] = self::$handlerStack;
                    }
                } else {
                    $options['handler']->push(self::$history);
                }
            }
            
            if (self::$debug) {
                $options['debug'] = true;
            }
            
            if (!isset($options['timeout'])) {
                $options['timeout'] = $this->timeout_in_seconds;
            }
        }
        
        try {
            $return = call_user_func_array([$this->guzzle, $method], $args);
        } finally {
            $this->query = [];
            $this->body = '';
            $this->headers = [];
            
            if (self::$verbose && isset($httpMethod)) {
                static::verbose(self::$requests);
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
     * Print formatted JSON responses to STDOUT
     *
     * @param $enable
     */
    public static function setVerbose($enable)
    {
        self::$verbose = $enable;
    }

    /**
     * Print HTTP requests/responses to STDOUT
     *
     * @param $enable
     */
    public static function setDebug($enable)
    {
        self::$debug = $enable;
    }

    /**
     * Output JSON when verbose mode is turned on
     *
     * @param $requests Array of requests captured by {@see GuzzleHttp\MiddleWare::history()}
     * @see \Akamai\Open\EdgeGrid\Client::setVerbose
     */
    protected function verbose($requests)
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
        
        $lastRequest = end($requests);
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

    /**
     * Create the Authentication header
     *
     * @param $method HTTP method
     * @param $path Request path
     * @return string
     * @link https://developer.akamai.com/introduction/Client_Auth.html
     */
    protected function createAuthHeader($method, $path)
    {
        $auth_header =
            'EG1-HMAC-SHA256 ' .
            'client_token=' . $this->auth['client_token'] . ';' .
            'access_token=' . $this->auth['access_token'] . ';' .
            'timestamp=' . $this->timestamp . ';' .
            'nonce=' . $this->nonce . ';';
        
        return $auth_header . 'signature=' . $this->signRequest($method, $path, $this->timestamp, $auth_header);
    }

    /**
     * Returns a signature of the given request, timestamp and auth_header
     *
     * @param string $method
     * @param string $path
     * @param string $timestamp
     * @param string $auth_header
     * @return string
     */
    protected function signRequest($method, $path, $timestamp, $auth_header)
    {
        return $this->makeBase64HmacSha256(
            $this->makeDataToSign($method, $path, $auth_header),
            $this->makeSigningKey($timestamp)
        );
    }

    /**
     * Returns a string with all data that will be signed
     *
     * @param string $method
     * @param string $path
     * @param string $auth_header
     * @return string
     */
    protected function makeDataToSign($method, $path, $auth_header)
    {
        $query = '';
        if ($this->query) {
            $query .= '?';
            if (is_string($this->query)) {
                $query .= $this->query;
            } else {
                $query .= http_build_query($this->query, null, '&', PHP_QUERY_RFC3986);
            }
        }
        
        $data = implode(
            "\t",
            [
                strtoupper($method),
                'https',
                $this->host,
                $path . $query,
                $this->canonicalizeHeaders(),
                (strtoupper($method) == 'POST') ? $this->makeContentHash() : '',
                $auth_header
            ]
        );
        
        return $data;
    }

    /**
     * Returns headers in normalized form
     *
     * @return string
     */
    protected function canonicalizeHeaders()
    {
        $canonicalized_headers = [];
        $headers = array_combine(array_map('strtolower', array_keys($this->headers)), array_values($this->headers));
        
        foreach ($this->headers_to_sign as $key) {
            $key = strtolower($key);
            if (isset($headers[$key])) {
                if (is_array($headers[$key]) && sizeof($headers[$key]) >= 1) {
                    $value = trim($headers[$key][0]);
                } elseif (is_array($headers[$key]) && sizeof($headers[$key]) == 0) {
                    continue;
                } else {
                    $value = trim($headers[$key]);
                }
                
                if (!empty($value)) {
                    $canonicalized_headers[$key] = preg_replace('/\s+/', ' ', $value);
                }
            }
        }
        
        ksort($canonicalized_headers);
        $serialized_header = '';
        foreach ($canonicalized_headers as $key => $value) {
            $serialized_header .= $key . ':' . $value . "\t";
        }

        return rtrim($serialized_header);
    }

    /**
     * Returns a hash of the HTTP POST body
     *
     * @return string
     */
    protected function makeContentHash()
    {
        if (empty($this->body)) {
            return '';
        } else {
            // Just substr, it'll return as much as it can
            return $this->makeBase64Sha256(substr($this->body, 0, $this->max_body_size));
        }
    }

    /**
     * Creates a signing key based on the secret and timestamp
     *
     * @param string $timestamp
     * @return string
     */
    protected function makeSigningKey($timestamp)
    {
        return self::makeBase64HmacSha256($timestamp, $this->auth['client_secret']);
    }

    /**
     * Returns Base64 encoded HMAC-SHA256 Hash
     *
     * @param string $data
     * @param string $key
     * @return string
     */
    protected function makeBase64HmacSha256($data, $key)
    {
        return base64_encode(hash_hmac('sha256', $data, $key, true));
    }

    /**
     * Returns Base64 encoded SHA256 Hash
     *
     * @param string $data
     * @return string
     */
    protected function makeBase64Sha256($data)
    {
        return base64_encode(hash('sha256', $data, true));
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
