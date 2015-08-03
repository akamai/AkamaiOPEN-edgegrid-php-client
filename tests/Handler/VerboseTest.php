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

class VerboseTest extends \PHPUnit_Framework_TestCase
{
    public function teardown()
    {
        Client::setVerbose(false);
    }

    public function testInstanceVerboseSingle()
    {
        $handler = $this->getMockHandler([new Response(200, [], json_encode(['test' => 'data']))]);
        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );
        $client->setAuth('test', 'test', 'test');

        $client->setInstanceVerbose(true);

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

    public function testInstanceVerboseMultiple()
    {
        $handler = $this->getMockHandler([
            new Response(200, [], json_encode(['test' => 'data'])),
            new Response(200, [], json_encode(['test' => 'data2', ["foo", "bar"], false, null, 123, 0.123]))
        ]);
        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );
        $client->setAuth('test', 'test', 'test');

        $client->setInstanceVerbose(true);

        ob_start();
        $client->get('/test1');
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

    public function testStaticVerboseSingle()
    {
        $handler = $this->getMockHandler([new Response(200, [], json_encode(['test' => 'data']))]);
        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );

        Client::setVerbose(true);

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

    public function testStaticVerboseMultiple()
    {
        $handler = $this->getMockHandler([
            new Response(200, [], json_encode(['test' => 'data'])),
            new Response(200, [], json_encode(['test' => 'data2', ["foo", "bar"], false, null, 123, 0.123]))
        ]);
        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );

        Client::setVerbose(true);

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

    public function testVerboseOverrideSingle()
    {
        $handler = $this->getMockHandler([new Response(200, [], json_encode(['test' => 'data']))]);
        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );

        Client::setVerbose(true);
        $client->setInstanceVerbose(false);

        ob_start();
        $client->get('/test');
        $output = ob_get_clean();

        $expectedOutput = "";

        $this->assertEquals($expectedOutput, $output);
    }

    public function testVerboseOverrideMultiple()
    {
        $handler = $this->getMockHandler([
            new Response(200, [], json_encode(['test' => 'data'])),
            new Response(200, [], json_encode(['test' => 'data2', ["foo", "bar"], false, null, 123, 0.123]))
        ]);
        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );

        Client::setVerbose(true);
        $client->setInstanceVerbose(false);

        ob_start();
        $client->get('/test');
        $client->get('/test2');
        $output = ob_get_clean();

        $expectedOutput = "";

        $this->assertEquals($expectedOutput, $output);
    }

    public function testVerboseRedirect()
    {
        $handler = $this->getMockHandler([
            new Response(301, ["Location" => "http://example.org/redirected"], json_encode(['test' => 'data'])),
            new Response(200, [], json_encode(['test' => 'data2', ["foo", "bar"], false, null, 123, 0.123]))
        ]);

        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );

        Client::setVerbose(true);

        ob_start();
        $client->get('/redirect');
        $output = ob_get_clean();

        $expectedOutput = <<<EOF
[33;01m===> [VERBOSE] Redirected: http://example.org/redirected
[39;49;00m
[36;01m===> [VERBOSE] Response: 
[33;01m{
    "test": "data2",
    "0": [
        "foo",
        "bar"
    ],
    "1": false,
    "2": null,
    "3": 123,
    "4": 0.123
}[39;49;00m

EOF;

        $this->assertEquals($expectedOutput, $output);
    }

    public function testVerboseNonJson()
    {
        $handler = $this->getMockHandler([
            new Response(200, [], "String body")
        ]);

        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );

        Client::setVerbose(true);

        ob_start();
        try {
            $client->get('/error');
        } catch (\GuzzleHttp\Exception\ClientException $e) {
        }
        $output = ob_get_clean();

        $expectedOutput = <<<EOF
[36;01m===> [VERBOSE] Response: 
[33;01mString body[39;49;00m

EOF;

        $this->assertEquals($expectedOutput, $output);
    }

    public function testVerboseRequestHandler()
    {
        $handler = $this->getMockHandler([
            new Response(200, [], "String body")
        ]);

        $client = new Client(
            [
                'base_uri' => 'http://example.org',
            ]
        );

        Client::setVerbose(true);

        ob_start();
        try {
            $client->get('/error', ['handler' => $handler]);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
        }
        $output = ob_get_clean();

        $expectedOutput = <<<EOF
[36;01m===> [VERBOSE] Response: 
[33;01mString body[39;49;00m

EOF;

        $this->assertEquals($expectedOutput, $output);
    }

    public function testVerboseNoResponse()
    {
        $handler = $this->getMockHandler([
            new Response(101)
        ]);

        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );

        Client::setVerbose(true);

        ob_start();
        try {
            $client->get('/error');
        } catch (\GuzzleHttp\Exception\ClientException $e) {
        }
        $output = ob_get_clean();

        $expectedOutput = <<<EOF
[36;01m===> [VERBOSE] Response: 
[33;01mNo response body returned[39;49;00m

EOF;

        $this->assertEquals($expectedOutput, $output);
    }

    public function testVerboseError()
    {
        $handler = $this->getMockHandler([
            new Response(404, [], json_encode(['test' => 'data2', ["foo", "bar"], false, null, 123, 0.123]))
        ]);

        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );

        $fp = fopen('php://memory', 'a+');
        Client::setVerbose([STDOUT, $fp]);

        ob_start();
        try {
            $client->get('/error');
        } catch (\GuzzleHttp\Exception\ClientException $e) {
        }
        $this->assertEmpty(ob_get_clean());

        fseek($fp, 0);
        $output = '';
        do {
            $output .= fgets($fp);
        } while (!feof($fp));

        $expectedOutput = <<<EOF
[31;01m===> [ERROR] An error occurred: 
[33;01m{
    "test": "data2",
    "0": [
        "foo",
        "bar"
    ],
    "1": false,
    "2": null,
    "3": 123,
    "4": 0.123
}[39;49;00m

EOF;

        $this->assertEquals($expectedOutput, $output);
    }

    public function testVerboseErrorNonJson()
    {
        $handler = $this->getMockHandler([
            new Response(404, [], "String body")
        ]);

        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );

        $fp = fopen('php://memory', 'a+');
        Client::setVerbose([STDOUT, $fp]);

        ob_start();
        try {
            $client->get('/error');
        } catch (\GuzzleHttp\Exception\ClientException $e) {
        }
        $this->assertEmpty(ob_get_clean());

        fseek($fp, 0);
        $output = '';
        do {
            $output .= fgets($fp);
        } while (!feof($fp));

        $expectedOutput = <<<EOF
[31;01m===> [ERROR] An error occurred: 
[33;01mString body[39;49;00m

EOF;

        $this->assertEquals($expectedOutput, $output);
    }

    public function testVerboseMixed()
    {
        $handler = $this->getMockHandler([
            new Response(200, [], json_encode(['test' => 'data'])),
            new Response(404, [], json_encode(['test' => 'data2', ["foo", "bar"], false, null, 123, 0.123]))
        ]);

        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );

        $fp = fopen('php://memory', 'a+');
        Client::setVerbose(['php://output', $fp]);

        ob_start();
        try {
            $client->get('/success');
            $client->get('/error');
        } catch (\GuzzleHttp\Exception\ClientException $e) {
        }

        $expectedOutput = <<<EOF
\x1b[36;01m===> [VERBOSE] Response: 
\x1b[33;01m{
    "test": "data"
}\x1b[39;49;00m

EOF;

        $this->assertEquals($expectedOutput, ob_get_clean());

        fseek($fp, 0);
        $output = '';
        do {
            $output .= fgets($fp);
        } while (!feof($fp));

        $expectedError = <<<EOF
[31;01m===> [ERROR] An error occurred: 
[33;01m{
    "test": "data2",
    "0": [
        "foo",
        "bar"
    ],
    "1": false,
    "2": null,
    "3": 123,
    "4": 0.123
}[39;49;00m

EOF;

        $this->assertEquals($expectedError, $output);
    }

    public function testVerboseResponseExceptionNoCode()
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

        Client::setVerbose(true);

        ob_start();
        try {
            $client->get('/error');
        } catch (\GuzzleHttp\Exception\RequestException $e) {
        }
        $output = ob_get_clean();

        $expectedOutput = <<<EOF
[31;01m===> [ERROR] An error occurred: 
[33;01mError message[39;49;00m

EOF;

        $this->assertEquals($expectedOutput, $output);
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

        Client::setVerbose(true);

        ob_start();
        try {
            $client->get('/error');
        } catch (\GuzzleHttp\Exception\RequestException $e) {
        }
        $output = ob_get_clean();

        $expectedOutput = <<<EOF
[31;01m===> [ERROR] An error occurred: 
[33;01m500: Error message[39;49;00m

EOF;

        $this->assertEquals($expectedOutput, $output);
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

        Client::setVerbose(true);

        ob_start();
        try {
            $client->get('/error');
        } catch (\GuzzleHttp\Exception\RequestException $e) {
        }
        $output = ob_get_clean();

        $expectedOutput = <<<EOF
[31;01m===> [ERROR] An error occurred: 
[33;01m500: Error message
[33;01m{"errorString":"An error"}[39;49;00m

EOF;

        $this->assertEquals($expectedOutput, $output);
    }

    public function testVerboseSingleStreamString()
    {
        $verbose = new \Akamai\Open\EdgeGrid\Handler\Verbose('php://memory');

        $fp = \PHPUnit_Framework_Assert::readAttribute($verbose, 'outputStream');
        $fp2 = \PHPUnit_Framework_Assert::readAttribute($verbose, 'errorStream');

        $this->assertSame($fp, $fp2);
        $this->assertTrue(stream_get_meta_data($fp)['uri'] == 'php://memory');
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Unable to use output stream: error://stream
     */
    public function testVerboseSingleStreamStringInvalid()
    {
        $verbose = new \Akamai\Open\EdgeGrid\Handler\Verbose('error://stream');
    }

    public function testVerboseDualStreamString()
    {
        $verbose = new \Akamai\Open\EdgeGrid\Handler\Verbose('php://memory', 'php://temp');

        $fp = \PHPUnit_Framework_Assert::readAttribute($verbose, 'outputStream');
        $fp2 = \PHPUnit_Framework_Assert::readAttribute($verbose, 'errorStream');

        $this->assertNotSame($fp, $fp2);
        $this->assertTrue(stream_get_meta_data($fp)['uri'] == 'php://memory');
        $this->assertTrue(stream_get_meta_data($fp2)['uri'] == 'php://temp');
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Unable to use error stream: error://stream
     */
    public function testVerboseDualStreamStringErrorInvalid()
    {
        $verbose = new \Akamai\Open\EdgeGrid\Handler\Verbose('php://input', 'error://stream');
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Unable to use output stream: error://stream
     */
    public function testVerboseDualStreamStringInvalid()
    {
        $verbose = new \Akamai\Open\EdgeGrid\Handler\Verbose('error://stream', 'error://stream2');
    }

    public function getMockHandler($request, array &$container = null)
    {
        $client = new \Akamai\Open\EdgeGrid\Tests\ClientTest();
        return $client->getMockHandler($request, $container);
    }
}
