<?php
/**
 * Akamai {OPEN} EdgeGrid Auth Client
 *
 * @author Davey Shafik <dshafik@akamai.com>
 * @copyright Copyright 2016 Akamai Technologies, Inc. All rights reserved.
 * @license Apache 2.0
 * @link https://github.com/akamai-open/AkamaiOPEN-edgegrid-php-client
 * @link https://developer.akamai.com
 * @link https://developer.akamai.com/introduction/Client_Auth.html
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
     * @param $section
     * @param $file
     */
    public function testCreateFromEdgeRc($section, $file)
    {
        $_SERVER['HOME'] = __DIR__ . '/../edgerc';

        $authentication = \Akamai\Open\EdgeGrid\Handler\Authentication::createFromEdgeRcFile($section, $file);
        $this->assertInstanceOf(\Akamai\Open\EdgeGrid\Handler\Authentication::class, $authentication);
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

        $timestamp = $this->prophesize(\Akamai\Open\EdgeGrid\Authentication\Timestamp::class);
        $timestamp->__toString()->willReturn($options['timestamp']);
        $timestamp->isValid()->willReturn(true);
        $nonce = $this->prophesize(\Akamai\Open\EdgeGrid\Authentication\Nonce::class);
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
        $handler->before('history', $auth, 'signer');

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

        $this->assertEquals(1, count($container));
        $request = $container[0]['request'];
        $headers = $request->getHeaders();

        $this->assertArrayHasKey('Authorization', $headers);
        $this->assertEquals(1, count($headers['Authorization']));
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
        $handler->before('history', $auth, 'signer');

        $client = new \GuzzleHttp\Client([
            'base_uri' => 'http://example.org',
            'handler' => $handler,
        ]);

        $client->get('/test');

        $this->assertEquals(1, count($container));
        $request = $container[0]['request'];
        $this->assertInstanceOf(\Psr\Http\Message\RequestInterface::class, $request);
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
        $handler->before('history', $auth, 'signer');

        $client = new \GuzzleHttp\Client(
            [
                'handler' => $handler,
            ]
        );

        $client->get('https://test-akamaiapis.net');
    }

    public function testProxyNonFluent()
    {
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
        $auth = new \Akamai\Open\EdgeGrid\Handler\Authentication();
        $auth->setHost('test.host');
    }
}
