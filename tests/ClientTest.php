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
        Client::setVerbose(false);
        Client::setDebug(false);
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
        $container = [];
        $handler = $this->getMockHandler([new Response(200)], $container);
        
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
        
        $this->assertArrayHasKey('Authorization', $headers);
        $this->assertEquals(1, sizeof($headers['Authorization']));
        $this->assertEquals($result, $headers['Authorization'][0]);
    }
    
    /**
     * @dataProvider createFromEdgeRcProvider
     */
    public function testCreateFromEdgeRcDefault($section, $file)
    {
        $_SERVER['HOME'] = __DIR__ .'/edgerc';
        $client = \Akamai\Open\EdgeGrid\Client::createFromEdgeRcFile($section, $file);
        $authentication = \PHPUnit_Framework_Assert::readAttribute($client, 'authentication');
        
        $this->assertInstanceOf(\Akamai\Open\EdgeGrid\Client::CLASS, $client);
        $this->assertEquals(
            [
                'client_token' => "akab-client-token-xxx-xxxxxxxxxxxxxxxx",
                'client_secret' => "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=",
                'access_token' => "akab-access-token-xxx-xxxxxxxxxxxxxxxx"
            ],
            \PHPUnit_Framework_Assert::readAttribute($authentication, 'auth')
        );
        $this->assertEquals(
            'https://akaa-baseurl-xxxxxxxxxxx-xxxxxxxxxxxxx.luna.akamaiapis.net',
            $client->getConfig('base_uri')
        );
        $this->assertEquals(2048, \PHPUnit_Framework_Assert::readAttribute($authentication, 'max_body_size'));
    }
    
    public function testHostnameWithTrailingSlash()
    {
        $client = new \Akamai\Open\EdgeGrid\Client();
        $client->setHost('akaa-baseurl-xxxxxxxxxxx-xxxxxxxxxxxxx.luna.akamaiapis.net/');
        
        $this->assertEquals(
            'akaa-baseurl-xxxxxxxxxxx-xxxxxxxxxxxxx.luna.akamaiapis.net',
            $client->getConfig('headers')['Host']
        );
    }

    public function testDefaultTimeout()
    {
        // Mock the response, we don't care about it
        $container = [];
        $handler = $this->getMockHandler([new Response(200)], $container);

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
        $container = [];
        $handler = $this->getMockHandler([new Response(200), new Response(200)], $container);

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
        $container = [];
        $handler = $this->getMockHandler([new Response(200), new Response(200), new Response(200)], $container);
        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );

        $this->assertArrayHasKey('timeout', $client->getConfig());
        $this->assertEquals(Client::DEFAULT_REQUEST_TIMEOUT, $client->getConfig('timeout'));

        $client->get('/test', ['timeout' => 2]);
        $this->assertEquals(2, end($container)['options']['timeout']);
        $this->assertEquals(Client::DEFAULT_REQUEST_TIMEOUT, $client->getConfig('timeout'));
        
        $client->setTimeout(5);
        $client->get('/test');
        
        $this->assertEquals(5, $client->getConfig('timeout'));
        $this->assertEquals(5, end($container)['options']['timeout']);
    }
    
    public function testStaticDebugSingle()
    {
        $container = [];
        $handler = $this->getMockHandler([new Response(200)], $container);
        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );

        Client::setDebug(true);

        $client->get('/test');
        $this->assertEquals(true, is_resource(end($container)['options']['debug']));
    }
    
    public function testInstanceDebugSingle()
    {
        $container = [];
        $handler = $this->getMockHandler([new Response(200)], $container);
        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );
        $client->setAuth('test', 'test', 'test');

        $client->setInstanceDebug(true);
        $client->get('/test');
        $this->assertEquals(true, is_resource(end($container)['options']['debug']));
    }

    public function testDebugOverrideSingle()
    {
        $container = [];
        $handler = $this->getMockHandler([new Response(200), new Response(200)], $container);
        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );
        $client->setAuth('test', 'test', 'test');

        Client::setDebug(true);
        $client->setInstanceDebug(false);

        $client->get('/test');
        $this->assertEquals(false, is_resource(end($container)['options']['debug']));
    }

    public function testInstanceDebugOptionSingle()
    {
        $container = [];
        $handler = $this->getMockHandler([new Response(200), new Response(200), new Response(200)], $container);
        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );
        $client->setAuth('test', 'test', 'test');

        $client->get('/test', ['debug' => true]);
        $this->assertEquals(true, is_resource(end($container)['options']['debug']));

        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );
        $client->setAuth('test', 'test', 'test');
        $client->setDebug(true);
        $client->get('/test', ['debug' => false]);
        $this->assertEquals(false, is_resource(end($container)['options']['debug']));
        $this->assertFalse(end($container)['options']['debug']);

        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );
        $client->setAuth('test', 'test', 'test');
        Client::setDebug(true);
        $client->get('/test', ['debug' => false]);
        $this->assertEquals(false, is_resource(end($container)['options']['debug']));
        $this->assertFalse(end($container)['options']['debug']);
    }
    
    public function testNonApiCall()
    {
        $container = [];
        $handler = $this->getMockHandler([new Response(200), new Response(200), new Response(200)], $container);
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
        
        $uri = $client->getConfig('base_uri');
        $this->assertEquals('https', parse_url($uri, PHP_URL_SCHEME));
    }

    /**
     * @param $response
     * @param $path
     * @param $expected
     * @dataProvider loggingProvider
     */
    public function testLogging($method, $responses, $path, $expected)
    {
        $handler = $this->getMockHandler($responses);
        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );

        $logHandler = $this->setLogger($client);
        
        try {
            $client->{$method}($path);
        } catch (\Exception $e) {
        }

        $records = [];
        foreach ($logHandler->getRecords() as $record) {
            $records[] = $record['message'];
        }
        $this->assertEquals($expected, $records);
    }
    
    public function testLoggingRedirect()
    {
        $handler = $this->getMockHandler([
            new Response(301, ['Location' => '/redirected']),
            new Response(200, ['Content-Type' => 'application/json'])
        ]);
        
        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );
        
        $logHandler = $this->setLogger($client);
        
        try {
            $client->get("/redirect");
        } catch (\Exception $e) {
        }

        $records = [];
        foreach ($logHandler->getRecords() as $record) {
            $records[] = $record['message'];
        }
        $this->assertEquals(["GET /redirect 301 ", "GET /redirected 200 application/json"], $records);
    }
    
    public function testLoggingDefault()
    {
        $client = new Client();
        $client->setLogger();
        
        $logger = \PHPUnit_Framework_Assert::readAttribute($client, 'logger');
        $this->assertInstanceOf(
            \Closure::CLASS,
            $logger
        );
        
        $reflection = new \ReflectionFunction($logger);
        $args = $reflection->getParameters();
        $this->assertTrue(array_shift($args)->isCallable());
    }

    public function testLoggingRequestHandler()
    {
        $handler = $this->getMockHandler([
            new Response(200, ['Content-Type' => 'application/json'])
        ]);

        $client = new Client(
            [
                'base_uri' => 'http://example.org',
            ]
        );
        $logHandler = $this->setLogger($client);

        $client->get("/test", ['handler' => $handler]);
        $records = [];
        foreach ($logHandler->getRecords() as $record) {
            $records[] = $record['message'];
        }
        $this->assertEquals(["GET /test 200 application/json"], $records);
    }
    
    public function testSetSimpleLog()
    {
        $handler = $this->getMockHandler([
            new Response(200, ['Content-Type' => 'application/json'])
        ]);

        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );

        $fp = fopen("php://memory", "w+");
        $client->setSimpleLog($fp, "{method} {target} {code}");
        $client->get("/test");

        fseek($fp, 0);
        $this->assertEquals("GET /test 200", fgets($fp));
    }
    
    public function makeAuthHeaderProvider()
    {
        $testdata = json_decode(file_get_contents(__DIR__ . '/testdata.json'), true);
        $tests = $testdata['tests'];
        unset($testdata['tests']);
            
        foreach ($tests as $test) {
            yield [
                'name' => $test['testName'],
                'options' => array_merge($testdata, $test['request']),
                'request' => array_merge(['data' => ""], $test['request']),
                'result' => $test['expectedAuthorization'],
            ];
        }
    }
    
    public function createFromEdgeRcProvider()
    {
        return [
            [
                'section' => null,
                'file' => null,
            ],
            [
                'section' => 'default',
                'file' => null,
            ],
            [
                'section' => 'testing',
                'file' => __DIR__ . '/edgerc/.edgerc.testing',
            ],
            [
                'section' => 'testing',
                'file' => __DIR__ . '/edgerc/.edgerc.default-testing',
            ]
        ];
    }
    
    public function loggingProvider()
    {
        return [
            [
                'get',
                [new Response(200)],
                '/test',
                [
                    "GET /test 200 "
                ]
            ],
            [
                'get',
                [new Response(404)],
                '/error',
                [
                    "GET /error 404 "
                ]
            ],
            [
                'post',
                [new Response(500)],
                '/error',
                [
                    "POST /error 500 "
                ]
            ],
            [
                'put',
                [new Response(200, ['Content-Type' => 'application/json'])],
                '/test',
                [
                    "PUT /test 200 application/json"
                ]
            ],
            [
                'options',
                [new Response(405, ['Content-Type' => 'application/json'])],
                '/error',
                [
                    "OPTIONS /error 405 application/json"
                ]
            ],
            [
                'get',
                [new Response(503, ['Content-Type' => 'text/csv'])],
                '/error',
                [
                    "GET /error 503 text/csv"
                ]
            ],
            [
                'get',
                [
                    new Response(301, ['Location' => '/redirect']),
                    new Response(200)
                ],
                '/notthere',
                [
                    "GET /notthere 301 ",
                    "GET /redirect 200 ",
                ]
            ]
        ];
    }

    public function getMockHandler($requests, array &$container = null)
    {
        $mock = new MockHandler($requests);
        $container = [];
        $history = Middleware::history($container);

        $handler = HandlerStack::create($mock);
        $handler->push($history, 'history');
        
        return $handler;
    }

    /**
     * @return Client
     */
    protected function setLogger(Client $client)
    {
        $handler = new \Monolog\Handler\TestHandler();
        $logger = new \Monolog\Logger("Test Logger", [$handler]);
        $client->setLogger($logger, "{method} {target} {code} {res_header_content-type}");
        
        return $handler;
    }
}
