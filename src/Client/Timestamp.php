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
 * Generates an Akamai formatted Date for each request
 *
 * @package Akamai {OPEN} EdgeGrid Client
 * @subpackage Utility
 */
class Timestamp
{
    /**
     * Return the timestamp when cast to string
     *
     * @return string Returns the date
     */
    public function __toString()
    {
        return (new \DateTime(null, new \DateTimeZone('UTC')))->format('Ymd\TH:i:sO');
    }
}
