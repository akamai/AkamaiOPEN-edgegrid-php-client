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
namespace Akamai\Open\EdgeGrid\Authentication;

/**
 * Generates an Akamai formatted Date for each request
 *
 * @package Akamai {OPEN} EdgeGrid Auth
 */
class Timestamp
{
    /**
     * @var \DateTime Signing Timestamp
     */
    protected $timestamp;

    /**
     * @var string \DateInterval spec
     */
    protected $validFor = 'PT10S';

    /**
     * Signing Timestamp
     */
    public function __construct()
    {
        $this->timestamp = new \DateTime("now", new \DateTimeZone('UTC'));
    }

    /**
     * Return true is timestamp is less than 10s old
     *
     * @return bool
     */
    public function isValid()
    {
        $now = new \DateTime("now", new \DateTimeZone('UTC'));
        $timestamp = clone $this->timestamp;

        return $timestamp->add(new \DateInterval($this->validFor)) >= $now;
    }

    /**
     * Set how long the current timestamp is considered valid
     *
     * @see \DateInterval
     * @param string $interval A \DateInterval time spec
     * @return $this
     */
    public function setValidFor($interval)
    {
        $this->validFor = $interval;
        return $this;
    }

    /**
     * Return the timestamp when cast to string
     *
     * @return string Returns the date
     */
    public function __toString()
    {
        return $this->timestamp->format('Ymd\TH:i:sO');
    }
}
