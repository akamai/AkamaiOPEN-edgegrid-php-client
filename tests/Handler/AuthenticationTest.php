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
namespace Akamai\Open\EdgeGrid\Tests\Handler;

use GuzzleHttp\Psr7\Response;

/**
 * @requires PHP 5.5
 */
class AuthenticationTest extends \Akamai\Open\EdgeGrid\Tests\ClientTest
{
    /**
     * @dataProvider createFromEdgeRcProvider
     */
    public function testCreateFromEdgeRc($section, $file)
    {
        $_SERVER['HOME'] = __DIR__ . '/../edgerc';

        $guzzle = new \GuzzleHttp\Client();
        $authentication = \Akamai\Open\EdgeGrid\Handler\Authentication::createFromEdgeRcFile($section, $file);
        $this->assertInstanceOf(\Akamai\Open\EdgeGrid\Handler\Authentication::CLASS, $authentication);
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
        $container = [];
        $handler = $this->getMockHandler([new Response(200)], $container);

        $timestamp = $this->prophesize(\Akamai\Open\EdgeGrid\Authentication\Timestamp::CLASS);
        $timestamp->__toString()->willReturn($options['timestamp']);
        $timestamp->isValid()->willReturn(true);
        $nonce = $this->prophesize(\Akamai\Open\EdgeGrid\Authentication\Nonce::CLASS);
        $nonce->__toString()->willReturn($options['nonce']);

        $auth = new \Akamai\Open\EdgeGrid\Handler\Authentication();
        $auth->setSigner();
        $auth->setAuth($options['client_token'], $options['client_secret'], $options['access_token']);
        $auth->setMaxBodySize($options['max_body']);
        $auth->setTimestamp($timestamp->reveal());
        $auth->setNonce($nonce->reveal());

        if (isset($options['headers_to_sign'])) {
            $auth->setHeadersToSign($options['headers_to_sign']);
        }

        // Because of PSR-7 immutability the history handler has
        // to be the last one, otherwise it doesn't get the latest
        // instance of the Request.
        $handler->before('history', $auth, "signer");

        $client = new \GuzzleHttp\Client(
            array_merge($options, [
                'base_uri' => $options['base_url'],
                'handler' => $handler,
            ])
        );

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

    public function testHandlerChainingNotAuthenticated()
    {
        $container = [];
        $handler = $this->getMockHandler([new Response(200)], $container);

        $auth = new \Akamai\Open\EdgeGrid\Handler\Authentication();

        // Because of PSR-7 immutability the history handler has
        // to be the last one, otherwise it doesn't get the latest
        // instance of the Request.
        $handler->before('history', $auth, "signer");

        $client = new \GuzzleHttp\Client([
            'base_uri' => 'http://example.org',
            'handler' => $handler,
        ]);

        $client->get('/test');

        $this->assertEquals(1, sizeof($container));
        $request = $container[0]['request'];
        $this->assertInstanceOf(\Psr\Http\Message\RequestInterface::CLASS, $request);
    }

    /**
     * @expectedException \Akamai\Open\EdgeGrid\Exception\HandlerException
     * @expectedExceptionMessage Signer not set, make sure to call setSigner first
     */
    public function testRequireSetSignerCall()
    {
        $container = [];
        $handler = $this->getMockHandler([new Response(200)], $container);

        $auth = new \Akamai\Open\EdgeGrid\Handler\Authentication();

        // Because of PSR-7 immutability the history handler has
        // to be the last one, otherwise it doesn't get the latest
        // instance of the Request.
        $handler->before('history', $auth, "signer");

        $client = new \GuzzleHttp\Client(
            [
                'handler' => $handler,
            ]
        );

        $client->get("https://test-akamaiapis.net");
    }

    public function testProxyNonFluent()
    {
        $container = [];
        $handler = $this->getMockHandler([new Response(200)], $container);

        $auth = new \Akamai\Open\EdgeGrid\Handler\Authentication();
        $auth->setSigner(new \Akamai\Open\EdgeGrid\Authentication());
        $auth->setHost('test.host');

        $this->assertEquals('test.host', $auth->getHost());
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Signer not set, make sure to call setSigner first
     */
    public function testProxyNoSigner()
    {
        $container = [];
        $handler = $this->getMockHandler([new Response(200)], $container);

        $auth = new \Akamai\Open\EdgeGrid\Handler\Authentication();
        $auth->setHost('test.host');
    }
}
