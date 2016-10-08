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
use Akamai\Open\EdgeGrid\Client;

/**
 * @requires PHP 5.5
 */
class DebugTest extends \PHPUnit_Framework_TestCase
{
    public function teardown()
    {
        Client::setDebug(false);
    }

    public function testInstanceDebug()
    {
        $handler = $this->getMockHandler([new Response(400, [], json_encode(['detail' => 'error info']))]);
        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );
        $client->setAuth('test', 'test', 'test');

        $fp = fopen('php://memory', 'a+');
        $client->setInstanceDebug($fp);

        ob_start();
        try {
            $client->get('/error');
        } catch (\Exception $e) {
        }

        $this->assertEmpty(ob_get_clean());

        $output = $this->readStreamData($fp);

        $expectedOutput = <<<EOF
[31;01m===> [ERROR] Call to /error failed with a 400 Bad Request result
===> [ERROR] This indicates a problem with authentication or headers.
===> [ERROR] Please ensure that the .edgerc file is formatted correctly.
===> [ERROR] If you still have issues, please use gen_edgerc.php to generate the credentials
===> [ERROR] Problem details:
error info[39;49;00m

EOF;

        $this->assertEquals($expectedOutput, $output);
    }

    public function testStaticDebug()
    {
        $handler = $this->getMockHandler([new Response(400, [], json_encode(['detail' => 'error info']))]);

        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );
        $client->setAuth('test', 'test', 'test');

        $fp = fopen('php://memory', 'a+');
        Client::setDebug($fp);

        ob_start();
        try {
            $client->get('/error');
        } catch (\Exception $e) {
        }

        $this->assertEmpty(ob_get_clean());

        $output = $this->readStreamData($fp);

        $expectedOutput = <<<EOF
[31;01m===> [ERROR] Call to /error failed with a 400 Bad Request result
===> [ERROR] This indicates a problem with authentication or headers.
===> [ERROR] Please ensure that the .edgerc file is formatted correctly.
===> [ERROR] If you still have issues, please use gen_edgerc.php to generate the credentials
===> [ERROR] Problem details:
error info[39;49;00m

EOF;

        $this->assertEquals($expectedOutput, $output);
    }

    public function testDebugOverride()
    {
        $handler = $this->getMockHandler([new Response(400, [], json_encode(['detail' => 'error string']))]);
        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );

        $fp = fopen('php://memory', 'a+');
        Client::setDebug($fp);
        $client->setInstanceDebug(false);

        ob_start();
        try {
            $client->get('/error');
        } catch (\Exception $e) {
        }
        $this->assertEmpty(ob_get_clean());

        $output = $this->readStreamData($fp);

        $expectedOutput = "";

        $this->assertEquals($expectedOutput, $output);
    }

    public function testInstanceOverrideStream()
    {
        $handler = $this->getMockHandler([new Response(400, [], json_encode(['detail' => 'error string']))]);
        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );

        $fp = fopen('php://memory', 'a+');
        Client::setDebug($fp);
        $fp2 = fopen('php://memory', 'a+');
        $client->setInstanceDebug($fp2);

        ob_start();
        try {
            $client->get('/error');
        } catch (\Exception $e) {
        }
        $this->assertEmpty(ob_get_clean());

        $output = $this->readStreamData($fp);

        $expectedOutput = "";
        $this->assertEquals($expectedOutput, $output);

        $output = $this->readStreamData($fp2);

        $expectedOutput = <<<EOF
[31;01m===> [ERROR] Call to /error failed with a 400 Bad Request result
===> [ERROR] This indicates a problem with authentication or headers.
===> [ERROR] Please ensure that the .edgerc file is formatted correctly.
===> [ERROR] If you still have issues, please use gen_edgerc.php to generate the credentials
===> [ERROR] Problem details:
error string[39;49;00m

EOF;

        $this->assertEquals($expectedOutput, $output);
    }

    public function testDebugMessages()
    {
        $handler = $this->getMockHandler([
            new Response(400, [], json_encode(['detail' => 'error message 1'])),
            new Response(401, [], json_encode(['detail' => 'error message 2'])),
            new Response(403, [], json_encode(['detail' => 'error message 3'])),
            new Response(404, [], json_encode(['detail' => 'error message 4']))
        ]);

        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );
        $client->setAuth('test', 'test', 'test');

        $fp = fopen('php://memory', 'a+');
        $client->setInstanceDebug($fp);

        ob_start();
        try {
            $client->get('/400');
        } catch (\Exception $e) {
        }
        try {
            $client->get('/401');
        } catch (\Exception $e) {
        }
        try {
            $client->get('/403');
        } catch (\Exception $e) {
        }
        try {
            $client->get('/404');
        } catch (\Exception $e) {
        }
        $this->assertEmpty(ob_get_clean());

        $output = $this->readStreamData($fp);

        $expectedOutput = <<<EOF
[31;01m===> [ERROR] Call to /400 failed with a 400 Bad Request result
===> [ERROR] This indicates a problem with authentication or headers.
===> [ERROR] Please ensure that the .edgerc file is formatted correctly.
===> [ERROR] If you still have issues, please use gen_edgerc.php to generate the credentials
===> [ERROR] Problem details:
error message 1[39;49;00m
[31;01m===> [ERROR] Call to /401 failed with a 401 Unauthorized result
===> [ERROR] This indicates a problem with authentication or headers.
===> [ERROR] Please ensure that the .edgerc file is formatted correctly.
===> [ERROR] If you still have issues, please use gen_edgerc.php to generate the credentials
===> [ERROR] Problem details:
error message 2[39;49;00m
[31;01m===> [ERROR] Call to /403 failed with a 403 Forbidden result
===> [ERROR] This indicates a problem with authorization.

===> [ERROR] Please ensure that the credentials you created for this script

===> [ERROR] have the necessary permissions in the Luna portal.

===> [ERROR] Problem details:
error message 3[39;49;00m
[31;01m===> [ERROR] Call to /404 failed with a 404 Not Found result
===> [ERROR] This means that the page does not exist as requested.

===> [ERROR] Please ensure that the URL you're calling is correctly formatted

===> [ERROR] or look at other examples to make sure yours matches.

===> [ERROR] Problem details:
error message 4[39;49;00m

EOF;

        $this->assertEquals($expectedOutput, $output);
    }

    public function testResponseNoDetail()
    {
        $handler = $this->getMockHandler([new Response(500, [], json_encode(['nodetail' => 'error info']))]);

        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );
        $client->setAuth('test', 'test', 'test');

        $fp = fopen('php://memory', 'a+');
        Client::setDebug($fp);

        ob_start();
        try {
            $client->get('/error');
        } catch (\Exception $e) {
        }

        $this->assertEmpty(ob_get_clean());

        $output = $this->readStreamData($fp);

        $expectedOutput = <<<EOF
[31;01m===> [ERROR] Call to /error failed with a 500 Internal Server Error result
===> [ERROR] Problem details:
{
    "nodetail": "error info"
}[39;49;00m

EOF;

        $this->assertEquals($expectedOutput, $output);
    }

    public function testResponseNoBody()
    {
        $handler = $this->getMockHandler([new Response(500, [])]);

        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );
        $client->setAuth('test', 'test', 'test');

        $fp = fopen('php://memory', 'a+');
        Client::setDebug($fp);

        ob_start();
        try {
            $client->get('/error');
        } catch (\Exception $e) {
        }

        $this->assertEmpty(ob_get_clean());

        $output = $this->readStreamData($fp);

        $expectedOutput = <<<EOF
[31;01m===> [ERROR] Call to /error failed with a 500 Internal Server Error result
===> [ERROR] Problem details:
No response body returned[39;49;00m

EOF;

        $this->assertEquals($expectedOutput, $output);
    }

    public function testResponseNoJsonBody()
    {
        $handler = $this->getMockHandler([new Response(500, [], "error info")]);

        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );
        $client->setAuth('test', 'test', 'test');

        $fp = fopen('php://memory', 'a+');
        Client::setDebug($fp);

        ob_start();
        try {
            $client->get('/error');
        } catch (\Exception $e) {
        }

        $this->assertEmpty(ob_get_clean());

        $output = $this->readStreamData($fp);

        $expectedOutput = <<<EOF
[31;01m===> [ERROR] Call to /error failed with a 500 Internal Server Error result
===> [ERROR] Problem details:
error info[39;49;00m

EOF;

        $this->assertEquals($expectedOutput, $output);
    }

    public function testStringResource()
    {
        $handler = new \Akamai\Open\EdgeGrid\Handler\Debug('php://stdout');
        $fp = \PHPUnit_Framework_Assert::readAttribute($handler, 'fp');
        $this->assertEquals('php://stdout', stream_get_meta_data($fp)['uri']);
    }

    /**
     * @expectedException \Akamai\Open\EdgeGrid\Exception\HandlerException\IOException
     * @expectedExceptionMessage Unable to use resource: fake://stream
     */
    public function testInvalidStringResource()
    {
        $handler = new \Akamai\Open\EdgeGrid\Handler\Debug('fake://stream');
    }

    public function testDebugResponseExceptionNoCode()
    {
        $handler = $this->getMockHandler([
            new \GuzzleHttp\Exception\RequestException("Error message", new \GuzzleHttp\Psr7\Request('GET', '/test'))
        ]);

        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );

        $fp = fopen('php://memory', 'a+');
        Client::setDebug($fp);

        ob_start();
        try {
            $client->get('/error');
        } catch (\GuzzleHttp\Exception\RequestException $e) {
        }
        $this->assertEmpty(ob_get_clean());

        $output = $this->readStreamData($fp);
        $this->assertEmpty($output);
    }

    public function testVerboseResponseExceptionWithCode()
    {
        $handler = $this->getMockHandler([
            new \GuzzleHttp\Exception\RequestException(
                "Error message",
                new \GuzzleHttp\Psr7\Request('GET', '/test'),
                new Response(500)
            )
        ]);

        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );

        $fp = fopen("php://memory", "a+");
        Client::setDebug($fp);

        ob_start();
        try {
            $client->get('/error');
        } catch (\GuzzleHttp\Exception\RequestException $e) {
        }
        $this->assertEmpty(ob_get_clean());

        $output = $this->readStreamData($fp);
        $this->assertEmpty($output);
    }

    public function testVerboseResponseExceptionWithBody()
    {
        $handler = $this->getMockHandler([
            new \GuzzleHttp\Exception\RequestException(
                "Error message",
                new \GuzzleHttp\Psr7\Request('GET', '/test'),
                new Response(500, [], json_encode(["errorString" => "An error"]))
            )
        ]);

        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );

        $fp = fopen('php://memory', 'a+');
        Client::setDebug($fp);

        ob_start();
        try {
            $client->get('/error');
        } catch (\GuzzleHttp\Exception\RequestException $e) {
        }
        $this->assertEmpty(ob_get_clean());

        $output = $this->readStreamData($fp);

        $this->assertEmpty($output);
    }

    public function getMockHandler($request, array &$container = null)
    {
        $client = new \Akamai\Open\EdgeGrid\Tests\ClientTest();
        return $client->getMockHandler($request, $container);
    }

    protected function readStreamData($fp)
    {
        fseek($fp, 0);
        $output = '';
        while (!feof($fp)) {
            $output .= fgets($fp);
        }

        return $output;
    }
}
