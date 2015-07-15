<?php
/**
 * Akamai {OPEN} EdgeGrid Auth for PHP
 *
 * @author Davey Shafik <dshafik@akamai.com>
 * @copyright Copyright 2015 Akamai Technologies, Inc. All rights reserved.
 * @license Apache 2.0
 * @link https://github.com/akamai-open/edgegrid-auth-php
 * @link https://developer.akamai.com
 * @link https://developer.akamai.com/introduction/Client_Auth.html
 */
namespace Akamai\Open\EdgeGrid\Client;

use Akamai\Open\EdgeGrid\Authentication;

/**
 * GuzzleHttp\Guzzle & Akamai\Open\EdgeGrid\Client Options Marshaller
 *
 * @package Akamai {OPEN} EdgeGrid Client
 */
class OptionsHandler
{
    /**
     * @var Authentication|bool
     */
    protected $authentication = false;

    /**
     * @var string URI Scheme
     */
    protected $scheme = 'https';
    
    /**
     * @var string Akamai {OPEN} API host
     */
    protected $host;

    /**
     * @var string Request Path
     */
    protected $path = '';

    /**
     * @var array GET args
     */
    protected $query = [];

    /**
     * @var array Request options
     */
    protected $options = [];

    /**
     * @var int Timeout in seconds
     */
    protected $timeout_in_seconds = \Akamai\Open\EdgeGrid\Client::DEFAULT_REQUEST_TIMEOUT;

    /**
     * Guzzle Options Handler
     *
     * @param Authentication|null $authentication
     */
    public function __construct(Authentication $authentication = null)
    {
        $this->authentication = $authentication;
    }

    /**
     * Get guzzle options
     *
     * @return array Guzzle options
     */
    public function getOptions()
    {
        if (!empty($this->authentication)) {
            $this->options = $this->getAuthenticatedOptions();
        }

        $this->options = $this->getStandardOptions();

        return $this->options;
    }


    /**
     * Get the authenticated Guzzle options array
     *
     * @return array
     */
    protected function getAuthenticatedOptions()
    {
        $options = $this->options;
        
        $options['query'] = $this->getQueryOption($options);
        $options['body'] = $this->getBodyOption($options);
        $options['headers'] = $this->getHeadersOption($options);
        $options['base_uri'] = $this->getHostOption($this->path, $options);

        if (strpos($options['base_uri'], 'https://') !== false &&
            strpos($options['base_uri'], 'akamaiapis.net') !== false
        ) {
            $this->authentication->setOptions($options);
            $options['headers']['Authorization'] = $this->authentication->createAuthHeader();
        }

        unset($options['form_params']);
        
        return $options;
    }

    /**
     * Retrieve the normalized Guzzle options array
     *
     * return array
     */
    protected function getStandardOptions()
    {
        $options = $this->options;
        
        $options['timeout'] = $this->getTimeoutOption();
        
        return $options;
    }

    /**
     * Get query option
     *
     * @param array $options Guzzle options
     * @return string
     */
    protected function getQueryOption($options)
    {
        $query = isset($options['query']) ? array_merge($options['query'], $this->query) : $this->query;

        return http_build_query($query, null, '&', PHP_QUERY_RFC3986);
    }

    /**
     * Get body option
     *
     * @param array $options
     * @return string
     */
    protected function getBodyOption($options)
    {
        if (isset($options['form_params'])) {
            if (is_array($options['form_params'])) {
                // Do not use PHP_QUERY_RFC3986 as with query params — same as Guzzle internally
                return http_build_query($options['form_params']);
            }
            return $options['form_params'];
        }

        if (isset($options['body'])) {
            return $options['body'];
        }

        return "";
    }

    /**
     * Get headers option
     *
     * @param array $options
     * @return array
     */
    protected function getHeadersOption($options)
    {
        return isset($options['headers']) && is_array($options['headers']) ? $options['headers'] : [];
    }

    /**
     * Get host option
     *
     * @param array $options Guzzle options
     * @return string
     * @throws \Exception
     */
    protected function getHostOption($path, $options)
    {
        if (isset($options['headers']['Host'])) {
            $this->setHost($options['headers']['Host']);
        }

        if (isset($options['base_uri'])) {
            $this->setHost($options['base_uri']);
            return $options['base_uri'];
        }

        if ($url = parse_url($path)) {
            if (isset($url['scheme'])) {
                $this->setScheme($url['scheme']);
            }
            
            if (isset($url['host'])) {
                $this->setHost($url['host']);
                return $url['scheme'] . '://' . $url['host'];
            }
        }

        if (isset($this->host)) {
            return $this->scheme . '://' . $this->host;
        }

        throw new \Exception("No Host set");
    }

    /**
     * Get timeout option
     *
     * @return int
     */
    protected function getTimeoutOption()
    {
        if (!isset($this->options['timeout'])) {
            return $this->timeout_in_seconds;
        }

        return $this->options['timeout'];
    }

    /**
     * Get URI scheme
     *
     * @return string
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * Set URI scheme
     *
     * @param $scheme
     * @return $this
     */
    public function setScheme($scheme)
    {
        $this->scheme = $scheme;
        
        return $this;
    }

    /**
     * Get host
     *
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }
    
    /**
     * Set host
     *
     * @param $host
     */
    public function setHost($host)
    {
        if (strpos($host, '://') !== false) {
            $url = parse_url($host);
            $this->scheme = $url['scheme'];
            $host = $url['host'];
        }

        if (substr($host, -1) == '/') {
            $host = substr($host, 0, -1);
        }

        $this->host = $host;
    }

    /**
     * Set Request path
     *
     * @param mixed $path
     * @return OptionsHandler
     */
    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * Set Guzzle options
     *
     * @param array $options
     * @return OptionsHandler
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
        return $this;
    }

    /**
     * Inject Authentication instance
     *
     * @param Authentication|bool $authentication
     */
    public function setAuthentication(Authentication $authentication)
    {
        $this->authentication = $authentication;
        return $this;
    }

    /**
     * Set GET args
     *
     * @param array $query
     */
    public function setQuery(array $query)
    {
        $this->query = $query;
        return $this;
    }

    /**
     * Get Request timeout
     *
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout_in_seconds;
    }
    
    /**
     * Set Request timeout
     *
     * @param int $timeout_in_seconds
     */
    public function setTimeout($timeout_in_seconds)
    {
        $this->timeout_in_seconds = $timeout_in_seconds;
        return $this;
    }
}
