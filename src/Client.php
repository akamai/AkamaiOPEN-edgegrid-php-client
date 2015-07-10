<?php
namespace Akamai\Open\EdgeGrid;

class Client {
    const DEFAULT_REQUEST_TIMEOUT = 10;

    /**
     * @var \GuzzleHttp\Client
     */
    protected $guzzle;

    /**
     * @var array
     */
    protected $auth = [];

    /**
     * @var string
     */
    protected $host;

    /**
     * @var int
     */
    protected $timeout_in_seconds = self::DEFAULT_REQUEST_TIMEOUT;

    /**
     * @var int
     */
    protected $max_body_size = 131072;

    /**
     * @var string
     */
    protected $body = '';

    /**
     * @var array
     */
    protected $headers = [];

    /**
     * @var array
     */
    protected $headers_to_sign = [];
    
    protected $timestamp;
    protected $nonce;

    public function __construct($options = [], Client\Timestamp $timestamp = null, Client\Nonce $nonce = null)
    {
        if (isset($options['base_uri']) && strpos($options['base_uri'], '://') === false) {
            $options['base_uri'] = 'https://' .$options['base_uri'];
        }
        
        $this->guzzle = new \GuzzleHttp\Client($options);
        
        if ($options['base_uri']) {
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

    public function setAuth($client_token, $client_secret, $access_token)
    {
        $this->auth = compact('client_token', 'client_secret', 'access_token');
    }

    public function setHost($host)
    {
        if (strpos($host, '://') !== false) {
            $host = parse_url($host, PHP_URL_HOST);
        }
        $this->host = $host;
    }
    
    public function setHeadersToSign(array $headers)
    {
        $this->headers_to_sign = $headers;
    }

    /**
     * @param int $max_body_size
     */
    public function setMaxBodySize($max_body_size)
    {
        $this->max_body_size = $max_body_size;
    }

    public function setTimeout($timeout_in_seconds)
    {
        $this->timeout_in_seconds = $timeout_in_seconds;
    }

    public function __call($method, $args)
    {
        // The only method that isn't a request-type method is getConfig
        // Don't create the auth header in that case
        if ($method != 'getConfig') {
            $httpMethod = str_replace('async', '', $method);
        
            $options = [];
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
            
            $this->body = isset($options['body']) ? $options['body'] : '';
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

            $options['headers']['Authorization'] = $this->createAuthHeader($httpMethod, $path);
        }
        
        $return = call_user_func_array([$this->guzzle, $method], $args);
        $this->body = '';
        $this->headers = '';
        
        return $return;
    }

    /**
     * @param string $path Path to .edgerc credentials file
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
        
        $options = parse_ini_file($file, true);
        if (!$options[$section]) {
            throw new \Exception("Section \"$section\" does not exist!");
        }
        
        $client = new static(array_merge($options, ['base_uri' => 'https://' . $options[$section]['host']]));
        $client->setAuth($options[$section]['client_token'], $options[$section]['client_secret'], $options[$section]['access_token']);
        if (isset($options[$section]['max-size'])) {
            $client->setMaxBodySize($options[$section]['max-size']);
        }

        return $client;
    }

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
     * @param array $headers
     * @param string $body
     * @param string $auth_header
     * @return string
     */
    protected function makeDataToSign($method, $path, $auth_header)
    {
        $data = implode(
            "\t",
            [
                strtoupper($method),
                'https',
                $this->host,
                $path,
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
     * @param array $headers
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
     * @param string $body POST body
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
} 
