<?php


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

        $expectedOutput =<<<EOF
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

        $expectedOutput =<<<EOF
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

        $expectedOutput =<<<EOF
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

        $expectedOutput =<<<EOF
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

        Client::setVerbose(true);

        ob_start();
        try {
            $client->get('/error');
        } catch (\GuzzleHttp\Exception\ClientException $e) {
        }
        $output = ob_get_clean();

        $expectedOutput =<<<EOF
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

        Client::setVerbose(true);

        ob_start();
        try {
            $client->get('/error');
        } catch (\GuzzleHttp\Exception\ClientException $e) {
        }
        $output = ob_get_clean();

        $expectedOutput =<<<EOF
[31;01m===> [ERROR] An error occurred: 
[33;01mString body[39;49;00m

EOF;

        $this->assertEquals($expectedOutput, $output);
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

        $expectedOutput =<<<EOF
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

        $expectedOutput =<<<EOF
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

        $expectedOutput =<<<EOF
[31;01m===> [ERROR] An error occurred: 
[33;01m500: Error message
[33;01m{"errorString":"An error"}[39;49;00m

EOF;

        $this->assertEquals($expectedOutput, $output);
    }
    
    public function getMockHandler($request, array &$container = null)
    {
        $client = new \Akamai\Open\EdgeGrid\Tests\ClientTest();
        return $client->getMockHandler($request, $container);
    }
}
