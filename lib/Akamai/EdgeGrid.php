<?php
/**
 *
 * Original Author: Hideki Okamoto <hokamoto@akamai.com>
 *
 * For more information visit https://developer.akamai.com
 *
 * Copyright 2014 Akamai Technologies, Inc. All rights reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Akamai;

/**
 * Client to access {OPEN} API
 *
 * @package Akamai
 * @author Hideki Okamoto <hokamoto@akamai.com>
 * @since PHP 5.4
 * @version 1.0.0
 */
class EdgeGrid
{
    const DEFAULT_TIMEOUT = 10;

    private $client_token;
    private $client_secret;
    private $consumer_domain;
    private $access_token;
    private $headers_to_sign;
    private $max_body;

    /**
     * Constructor
     *
     * @param string $client_token Client Token
     * @param string $client_secret Client Secret
     * @param string $consumer_domain Hostname in Base URL (ex. akab-xxxxxxxxxxxxxxxx-yyyyyyyyyyyyyyyy.luna.akamaiapis.net)
     * @param string $access_token Access Token
     * @param array $headers_to_sign Headers to be signed
     * @param int $max_body Maximum POST body size accepted.  This info is provided by individual APIs (default 131072)
     */
    function __construct($client_token, $client_secret, $consumer_domain, $access_token, $headers_to_sign = [], $max_body = 131072)
    {
        $this->client_token    = trim($client_token);
        $this->client_secret   = trim($client_secret);
        $this->consumer_domain = trim($consumer_domain);
        $this->access_token    = trim($access_token);
        $this->headers_to_sign = $headers_to_sign;
        $this->max_body        = $max_body;
    }

    /**
     * Makes a GET request
     *
     * @param string $path API Endpoint path (ex. /billing-usage/v1/reportSources)
     * @param array $headers Request headers
     * @param integer $timeout
     * @return HTTPResponse
     */
    public function get($path, $headers = [], $timeout = self::DEFAULT_TIMEOUT)
    {
        return $this->request('GET', $path, $headers, '', $timeout);
    }

    /**
     * Makes a POST request
     *
     * @param string $path API Endpoint path (ex. /billing-usage/v1/reportSources)
     * @param array $headers Request headers
     * @param string $body POST body
     * @param integer $timeout
     * @return HTTPResponse
     */
    public function post($path, $headers = [], $body, $timeout = self::DEFAULT_TIMEOUT)
    {
        return $this->request('POST', $path, $headers, $body, $timeout);
    }

    /**
     * Makes a PUT request
     *
     * @param string $path API Endpoint path (ex. /billing-usage/v1/reportSources)
     * @param array $headers Request headers
     * @param string $body PUT body
     * @param integer $timeout
     * @return HTTPResponse
     */
    public function put($path, $headers = [], $body, $timeout = self::DEFAULT_TIMEOUT)
    {
        return $this->request('PUT', $path, $headers, $body, $timeout);
    }

    /**
     * Makes a DELETE request
     *
     * @param string $path API Endpoint path (ex. /billing-usage/v1/reportSources)
     * @param array $headers Request headers
     * @param integer $timeout
     * @return HTTPResponse
     */
    public function delete($path, $headers = [], $timeout = self::DEFAULT_TIMEOUT)
    {
        return $this->request('DELETE', $path, $headers, '', $timeout);
    }

    /**
     * Makes a HTTP request
     *
     * @param $method
     * @param $path
     * @param array $headers
     * @param string $body
     * @param integer $timeout
     * @return HTTPResponse
     * @throws \Exception
     */
    private function request($method, $path, $headers = [], $body = '', $timeout = self::DEFAULT_TIMEOUT)
    {
        $headers['Authorization'] = $this->makeAuthHeader(
            strtoupper($method),
            $path,
            $headers,
            $body,
            self::getEdgeGridTimestamp(),
            self::makeNonce()
        );

        $client = new \GuzzleHttp\Client();
        $request = $client->createRequest(
            strtoupper($method),
            'https://' . $this->consumer_domain . $path,
            ['headers' => $headers, 'body' => $body, 'timeout' => $timeout]
        );

        try {
            $guzzle_response = $client->send($request);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            if ($e->hasResponse()) {
                $guzzle_response = $e->getResponse();
            } else {
                throw new \Exception(get_class($e));
            }
        }

        return new \Akamai\HTTPResponse(
            $guzzle_response->getProtocolVersion(),
            $guzzle_response->getStatusCode(),
            $guzzle_response->getReasonPhrase(),
            $guzzle_response->getHeaders(),
            (string)$guzzle_response->getBody()
        );
    }

    /**
     * Returns the computed Authorization header for the given request, timestamp and nonce
     *
     * @param string $method
     * @param string $path
     * @param array $headers
     * @param string $body
     * @param string $timestamp
     * @param string $nonce
     * @return string
     */
    public function makeAuthHeader($method, $path, $headers, $body, $timestamp, $nonce)
    {
        $auth_header =
            'EG1-HMAC-SHA256 ' .
            'client_token=' . $this->client_token . ';' .
            'access_token=' . $this->access_token . ';' .
            'timestamp=' . $timestamp . ';' .
            'nonce=' . $nonce . ';';

        if (strtoupper($method) === 'POST') {
            $signed_auth_header = $auth_header .
                'signature=' . $this->signRequest($method, $path, $headers, $body, $timestamp, $auth_header);
        } else {
            $signed_auth_header = $auth_header .
                'signature=' . $this->signRequest($method, $path, $headers, '', $timestamp, $auth_header);
        }

        return $signed_auth_header;
    }

    /**
     * Returns a signature of the given request, timestamp and auth_header
     *
     * @param string $method
     * @param string $path
     * @param array $headers
     * @param string $body
     * @param string $timestamp
     * @param string $auth_header
     * @return string
     */
    private function signRequest($method, $path, $headers, $body, $timestamp, $auth_header)
    {
        return self::makeBase64HmacSha256(
            $this->makeDataToSign($method, $path, $headers, $body, $auth_header),
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
    private function makeDataToSign($method, $path, $headers, $body, $auth_header)
    {
        return implode(
            "\t",
            [
                strtoupper($method),
                'https',
                $this->consumer_domain,
                $path,
                self::canonicalizeHeaders($headers),
                $this->makeContentHash($body),
                $auth_header
            ]
        );
    }

    /**
     * Returns headers in normalized form
     *
     * @param array $headers
     * @return string
     */
    private function canonicalizeHeaders($headers)
    {
        $canonicalized_headers = [];
        foreach ($headers as $key => $value) {
            if (in_array(strtolower($key), array_map('strtolower', $this->headers_to_sign))) {
                $canonicalized_headers[strtolower($key)] = preg_replace('/\s+/', ' ', trim($value));
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
    private function makeContentHash($body)
    {
        if (empty($body)) {
            return '';
        } elseif (strlen($body) > $this->max_body) {
            return self::makeBase64Sha256(substr($body, 0, $this->max_body));
        } else {
            return self::makeBase64Sha256($body);
        }
    }

    /**
     * Creates a signing key based on the secret and timestamp
     *
     * @param string $timestamp
     * @return string
     */
    private function makeSigningKey($timestamp)
    {
        return self::makeBase64HmacSha256($timestamp, $this->client_secret);
    }

    /**
     * Returns the current time in the format understood by {OPEN} API
     *
     * @return string Timestamp
     */
    public static function getEdgeGridTimestamp()
    {
        return (new \DateTime(null, new \DateTimeZone('UTC')))->format('Ymd\TH:i:sO');
    }

    /**
     * Returns a new nonce (unique identifier)
     *
     * @return string 16 bytes unique identifier
     */
    public static function makeNonce()
    {
        return bin2hex(openssl_random_pseudo_bytes(16));
    }

    /**
     * Returns Base64 encoded HMAC-SHA256 Hash
     *
     * @param string $data
     * @param string $key
     * @return string
     */
    public static function makeBase64HmacSha256($data, $key)
    {
        return base64_encode(hash_hmac('sha256', $data, $key, true));
    }

    /**
     * Returns Base64 encoded SHA256 Hash
     *
     * @param string $data
     * @return string
     */
    private static function makeBase64Sha256($data)
    {
        return base64_encode(hash('sha256', $data, true));
    }
}
