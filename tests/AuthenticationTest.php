<?php
/**
 *
 * Original Author: Davey Shafik <dshafik@akamai.com>
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
namespace Akamai\Open\EdgeGrid\Tests\Client;

class AuthenticationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider createAuthHeaderDataProvider
     */
    public function testCreateAuthHeader(
        $auth,
        $body,
        $expected,
        $headers,
        $headersToSign,
        $host,
        $maxBody,
        $method,
        $name,
        $nonce,
        $path,
        $query,
        $timestamp
    ) {
        $this->setName($name);
        
        $mockTimestamp = $this->prophesize(\Akamai\Open\EdgeGrid\Authentication\Timestamp::CLASS);
        $mockTimestamp->__toString()->willReturn($timestamp);
        $mockTimestamp->isValid()->willReturn(true);
        $mockNonce = $this->prophesize(\Akamai\Open\EdgeGrid\Authentication\Nonce::CLASS);
        $mockNonce->__toString()->willReturn($nonce);
        
        $authentication = new \Akamai\Open\EdgeGrid\Authentication();
        $authentication->setAuth($auth['client_token'], $auth['client_secret'], $auth['access_token']);
        $authentication->setHttpMethod($method);
        $authentication->setHeaders($headers);
        $authentication->setHeadersToSign($headersToSign);
        $authentication->setQuery($query);
        $authentication->setPath($path);
        $authentication->setHost($host);
        $authentication->setBody($body);
        $authentication->setMaxBodySize($maxBody);
        $authentication->setTimestamp($mockTimestamp->reveal());
        $authentication->setNonce($mockNonce->reveal());
        
        $result = $authentication->createAuthHeader();
        
        $this->assertEquals($expected, $result);
    }

    public function createAuthHeaderDataProvider()
    {
        $testdata = json_decode(file_get_contents(__DIR__ . '/testdata.json'), true);
        
        $defaults = [
            'auth' => [
                'client_token' => $testdata['client_token'],
                'client_secret' => $testdata['client_secret'],
                'access_token' => $testdata['access_token'],
            ],
            'host' => parse_url($testdata['base_url'], PHP_URL_HOST),
            'headersToSign' => $testdata['headers_to_sign'],
            'nonce' => $testdata['nonce'],
            'timestamp' => $testdata['timestamp'],
            'maxBody' => $testdata['max_body'],
        ];
        
        foreach ($testdata['tests'] as $test) {
            $data = array_merge($defaults, [
                'method' => $test['request']['method'],
                'path' => $test['request']['path'],
                'expected' => $test['expectedAuthorization'],
                'query' => (isset($test['request']['query'])) ? $test['request']['query'] : null,
                'body' => (isset($test['request']['data'])) ? $test['request']['data'] : null,
                'name' => $test['testName'],
            ]);
            
            $data['headers'] = [];
            if (isset($test['request']['headers'])) {
                array_walk_recursive($test['request']['headers'], function ($value, $key) use (&$data) {
                    $data['headers'][$key] = $value;
                });
            }
            
            ksort($data);
            
            yield $data;
        }
    }
}
