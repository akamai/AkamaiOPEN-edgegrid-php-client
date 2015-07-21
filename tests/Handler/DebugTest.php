<?php


namespace Akamai\Open\EdgeGrid\Tests\Handler;

use GuzzleHttp\Psr7\Response;
use Akamai\Open\EdgeGrid\Client;

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

        fseek($fp, 0);
        $output = '';
        do {
            $output .= fgets($fp);
        } while (!feof($fp));

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

        fseek($fp, 0);
        $output = '';
        do {
            $output .= fgets($fp);
        } while (!feof($fp));

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
        
        fseek($fp, 0);
        $output = '';
        while (!feof($fp)) {
            $output .= fgets($fp);
        }

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

        fseek($fp, 0);
        $output = '';
        while (!feof($fp)) {
            $output .= fgets($fp);
        }

        $expectedOutput = "";
        $this->assertEquals($expectedOutput, $output);

        fseek($fp2, 0);
        $output = '';
        while (!feof($fp2)) {
            $output .= fgets($fp2);
        }

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

        fseek($fp, 0);
        $output = '';
        do {
            $output .= fgets($fp);
        } while (!feof($fp));

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

    public function getMockHandler($request, array &$container = null)
    {
        $client = new \Akamai\Open\EdgeGrid\Tests\ClientTest();
        return $client->getMockHandler($request, $container);
    }
}
