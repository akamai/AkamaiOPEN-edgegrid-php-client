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
namespace Akamai\Open\EdgeGrid\Tests;

use Akamai\Open\EdgeGrid\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;

/**
 * Test for Akamai\Open\EdgeGrid\Client
 *
 * @author Davey Shafik <dshafik@akamai.com>
 * @since PHP 5.6
 * @version 1.0
 */
class ClientTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
    }
    
    /**
     * @param $name
     * @param $options
     * @param $request
     * @param $result
     * @dataProvider makeAuthHeaderProvider
     */
    public function testMakeAuthHeader($name, $options, $request, $result)
    {
        //$this->setName($name);
        
        // Mock the response, we don't care about it
        $mock = new MockHandler([new Response(200)]);
        $container = [];
        $history = Middleware::history($container);
        
        $handler = HandlerStack::create($mock);
        $handler->push($history);
        
        $timestamp = $this->prophesize(\Akamai\Open\EdgeGrid\Authentication\Timestamp::CLASS);
        $timestamp->__toString()->willReturn($options['timestamp']);
        $timestamp->isValid()->willReturn(true);
        $nonce = $this->prophesize(\Akamai\Open\EdgeGrid\Authentication\Nonce::CLASS);
        $nonce->__toString()->willReturn($options['nonce']);
        
        $client = new Client(
            array_merge($options, [
                'base_uri' => $options['base_url'], 
                'handler' => $handler,
                'timestamp' => $timestamp->reveal(),
                'nonce' => $nonce->reveal()
            ])
        );
        
        /* @var \GuzzleHttp\Client $client */
        $client->setAuth($options['client_token'], $options['client_secret'], $options['access_token']);
        $client->setMaxBodySize($options['max_body']);

        if (isset($options['headers_to_sign'])) {
            $client->setHeadersToSign($options['headers_to_sign']);
        }

        $headers = array();
        if (isset($request['headers'])) {
            array_walk_recursive($request['headers'], function ($value, $key) use (&$headers) {
                $headers[$key] = $value;
            });
        }
        
        $client->request(
            $request['method'],
            $request['path'],
            [
                'headers' => $headers,
                'body' => $request['data'],
            ]
        );
        
        $this->assertEquals(1, sizeof($container));
        $request = $container[0]['request'];
        $headers = $request->getHeaders();
        
        $this->assertEquals(1, sizeof($headers['Authorization']));
        $this->assertEquals($result, $headers['Authorization'][0]);
    }
    
    /**
     * @dataProvider createFromEdgeRcProvider
     */
    public function testCreateFromEdgeRcDefault()
    {
        $_SERVER['HOME'] = __DIR__ .'/edgerc';
        $client = \Akamai\Open\EdgeGrid\Client::createFromEdgeRcFile();
        
        $clientClosure = getPrivatePropertyTesterClosure($client);
        $authClosure = getPrivatePropertyTesterClosure($clientClosure('authentication'));
        
        $this->assertInstanceOf('\Akamai\Open\EdgeGrid\Client', $client);
        $this->assertEquals([
            'client_token' => "akab-client-token-xxx-xxxxxxxxxxxxxxxx",
            'client_secret' => "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=",
            'access_token' => "akab-access-token-xxx-xxxxxxxxxxxxxxxx"
        ], $authClosure('auth'));
        $this->assertEquals('akaa-baseurl-xxxxxxxxxxx-xxxxxxxxxxxxx.luna.akamaiapis.net', $clientClosure('optionsHandler')->getHost());
        $this->assertEquals(2048, $authClosure('max_body_size'));
    }
    
    public function testHostnameWithTrailingSlash()
    {
        $client = new \Akamai\Open\EdgeGrid\Client();
        $closure = function() {
            return $this->optionsHandler->getHost();
        };
        $tester = $closure->bindTo($client, $client);
        
        $client->setHost('akaa-baseurl-xxxxxxxxxxx-xxxxxxxxxxxxx.luna.akamaiapis.net/');
        
        $this->assertEquals('akaa-baseurl-xxxxxxxxxxx-xxxxxxxxxxxxx.luna.akamaiapis.net', $tester());
    }

    public function testDefaultTimeout()
    {
        // Mock the response, we don't care about it
        $mock = new MockHandler([new Response(200)]);
        $container = [];
        $history = Middleware::history($container);

        $handler = HandlerStack::create($mock);
        $handler->push($history);

        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );
        
        $this->assertArrayHasKey('timeout', $client->getConfig());
        $this->assertEquals(Client::DEFAULT_REQUEST_TIMEOUT, $client->getConfig('timeout'));

        $client->setAuth('test', 'test', 'test');
        
        $client->get('/test');
        $this->assertEquals(Client::DEFAULT_REQUEST_TIMEOUT, $container[0]['options']['timeout']);
    }

    public function testTimeoutOption()
    {
        // Mock the response, we don't care about it
        $mock = new MockHandler([new Response(200), new Response(200)]);
        $container = [];
        $history = Middleware::history($container);

        $handler = HandlerStack::create($mock);
        $handler->push($history);

        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
                'timeout' => 2
            ]
        );

        $this->assertArrayHasKey('timeout', $client->getConfig());
        $this->assertEquals(2, $client->getConfig('timeout'));

        $client->setAuth('test', 'test', 'test');

        $client->get('/test');
        $this->assertEquals(2, end($container)['options']['timeout']);
        
        $client->get('/test', ['timeout' => 5]);
        $this->assertEquals(5, end($container)['options']['timeout']);
    }

    public function testSetTimeout()
    {
        // Mock the response, we don't care about it
        $mock = new MockHandler([new Response(200), new Response(200), new Response(200)]);
        $container = [];
        $history = Middleware::history($container);

        $handler = HandlerStack::create($mock);
        $handler->push($history);

        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );

        $this->assertArrayHasKey('timeout', $client->getConfig());
        $this->assertEquals(Client::DEFAULT_REQUEST_TIMEOUT, $client->getConfig('timeout'));

        $client->setAuth('test', 'test', 'test');

        $client->get('/test', ['timeout' => 2]);
        $this->assertEquals(2, end($container)['options']['timeout']);

        $this->assertEquals(Client::DEFAULT_REQUEST_TIMEOUT, $client->getConfig('timeout'));
        
        $client->setTimeout(5);
        $client->get('/test');
        
        $this->assertEquals(Client::DEFAULT_REQUEST_TIMEOUT, $client->getConfig('timeout'));
        $this->assertEquals(5, end($container)['options']['timeout']);

        $client->get('/test', ['timeout' => 2]);
        $this->assertEquals(2, end($container)['options']['timeout']);
    }
    
    public function testVerboseSingle()
    {
        $mock = new MockHandler([new Response(200, [], json_encode(['test' => 'data']))]);
        $handler = HandlerStack::create($mock);
        
        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );
        $client->setAuth('test', 'test', 'test');
        
        $client->setVerbose(true);
        
        ob_start();
        $client->get('/test');
        $output = ob_get_clean();
        
        $expectedOutput = <<<EOF
\x1b[36;01m===> [VERBOSE] Response: 
\x1b[33;01m{
    "test": "data"
}\x1b[39;49;00m

EOF;
        
        $this->assertEquals($expectedOutput, $output);
    }

    public function testVerboseMultiple()
    {
        $mock = new MockHandler([new Response(200, [], json_encode(['test' => 'data'])), new Response(200, [], json_encode(['test' => 'data2', ["foo", "bar"], false, null, 123, 0.123]))]);
        $handler = HandlerStack::create($mock);

        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );
        $client->setAuth('test', 'test', 'test');

        $client->setVerbose(true);

        ob_start();
        $client->get('/test');
        $client->get('/test2');
        $output = ob_get_clean();

        $expectedOutput = <<<EOF
\x1b[36;01m===> [VERBOSE] Response: 
\x1b[33;01m{
    "test": "data"
}\x1b[39;49;00m
\x1b[36;01m===> [VERBOSE] Response: 
\x1b[33;01m{
    "test": "data2",
    "0": [
        "foo",
        "bar"
    ],
    "1": false,
    "2": null,
    "3": 123,
    "4": 0.123
}\x1b[39;49;00m

EOF;

        $this->assertEquals($expectedOutput, $output);
    }
    
    public function testDebugSingle()
    {
        $mock = new MockHandler([new Response(200)]);
        $container = [];
        $history = Middleware::history($container);

        $handler = HandlerStack::create($mock);
        $handler->push($history);

        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );
        $client->setAuth('test', 'test', 'test');

        $client->setDebug(true);
        $client->get('/test');
        $this->assertEquals(true, end($container)['options']['debug']);
    }
    
    public function testNonApiCall()
    {
        $mock = new MockHandler([new Response(200), new Response(200), new Response(200)]);
        $container = [];
        $history = Middleware::history($container);

        $handler = HandlerStack::create($mock);
        $handler->push($history);

        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );
        
        $response = $client->get('/test');
        $this->assertInstanceOf(\GuzzleHttp\Psr7\Response::CLASS, $response);
        $this->assertEquals('http', end($container)['request']->getUri()->getScheme());
        $this->assertEquals('example.org', end($container)['request']->getUri()->getHost());
        $this->assertArrayNotHasKey('Authentication', end($container)['request']->getHeaders());
        
        $response = $client->get('http://example.com/test');
        $this->assertInstanceOf(\GuzzleHttp\Psr7\Response::CLASS, $response);
        $this->assertEquals('http', end($container)['request']->getUri()->getScheme());
        $this->assertEquals('example.com', end($container)['request']->getUri()->getHost());
        $this->assertArrayNotHasKey('Authentication', end($container)['request']->getHeaders());

        $response = $client->get('https://example.net/test');
        $this->assertInstanceOf(\GuzzleHttp\Psr7\Response::CLASS, $response);
        $this->assertEquals('https', end($container)['request']->getUri()->getScheme());
        $this->assertEquals('example.net', end($container)['request']->getUri()->getHost());
        $this->assertArrayNotHasKey('Authentication', end($container)['request']->getHeaders());
    }
    
    public function testForceHttps()
    {
        $client = new Client(
            [
                'base_uri' => 'example.org'
            ]
        );
        
        $tester = getPrivatePropertyTesterClosure($client);
        $uri = $tester('guzzle')->getConfig('base_uri');
        $this->assertEquals('https', parse_url($uri, PHP_URL_SCHEME));
    }
    
    public function makeAuthHeaderProvider()
    {
        $testdata = json_decode(file_get_contents(__DIR__ . '/testdata.json'), true);
        $tests = $testdata['tests'];
        unset($testdata['tests']);
            
//        foreach ($tests as $test) {
        $test = end($tests);
            yield [
                'name' => $test['testName'],
                'options' => array_merge($testdata, $test['request']),
                'request' => array_merge(['data' => ""], $test['request']),
                'result' => $test['expectedAuthorization'],
            ];
//        }
    }
    
    public function createFromEdgeRcProvider()
    {
        return [
            [
                'section' => null,
                'file' => null,
            ],
            [
                'section' => 'testing',
                'file' => __DIR__ . '/.edgerc.testing',
            ],
            [
                'section' => 'testing',
                'file' => __DIR__ . '/.edgerc.default-testing',
            ]
        ];
    }
}
