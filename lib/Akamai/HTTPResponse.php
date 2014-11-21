<?php
/**
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
 * HTTP Response
 *
 * @package Akamai
 * @author Hideki Okamoto <hokamoto@akamai.com>
 * @since PHP 5.4
 * @version 1.0.0
 */
class HTTPResponse
{
    public $version;
    public $code;
    public $reason;
    public $headers;
    public $body;

    /**
     * Constructor
     *
     * @param string $version HTTP version
     * @param string $code HTTP status code
     * @param string $reason HTTP reason phrase
     * @param array $headers HTTP response headers
     * @param string $body HTTP response body
     */
    function __construct($version, $code, $reason, $headers, $body)
    {
        $this->version = trim($version);
        $this->code    = trim($code);
        $this->reason  = trim($reason);
        $this->headers = $headers;
        $this->body    = $body;
    }
}