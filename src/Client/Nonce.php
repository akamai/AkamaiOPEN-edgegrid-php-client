<?php
/**
 * Akamai {OPEN} EdgeGrid Client for PHP
 *
 * Akamai\Open\EdgeGrid\Client wraps GuzzleHttp\Client
 * providing request authentication/signing for Akamai
 * {OPEN} APIs.
 *
 * This client works _identically_ to GuzzleHttp\Client
 * with the following exceptions:
 *
 * - You *must* call {@see Akamai\Open\EdgeGrid\Client->setAuth()}
 *   before making a request.
 * - Will only make `https` requests
 * - Is intended _only_ for use with Akamai {OPEN} APIs (use Guzzle
 *   directly for other usages)
 *
 * @author Davey Shafik <dshafik@akamai.com>
 * @copyright Copyright 2015 Akamai Technologies, Inc. All rights reserved.
 * @license Apache 2.0
 * @link https://github.com/akamai-open/edgegrid-auth-php
 * @link https://developer.akamai.com
 * @link https://developer.akamai.com/introduction/Client_Auth.html
 */
namespace Akamai\Open\EdgeGrid\Client;

/**
 * Generates a random nonce for each request
 *
 * @package Akamai {OPEN} EdgeGrid Client
 * @subpackage Utility
 */
class Nonce
{
    /**
     * @var string The current random function to use
     */
    protected $function;

    /**
     * Constructor
     * 
     * Determines if PHP 7's random_bytes() can be used 
     */
    public function __construct()
    {
        $this->function = 'openssl_random_pseudo_bytes';

        // Use PHP 7's random_bytes()
        if (function_exists('random_bytes')) {
            $this->function = 'random_bytes';
        }
    }

    /**
     * Return the nonce when cast to string
     * 
     * @return string Returns the nonce
     */
    public function __toString()
    {
        // because ($this->function)() won't work til PHP 7 :(
        $function = $this->function;
        return bin2hex($function(16));
    }
}
