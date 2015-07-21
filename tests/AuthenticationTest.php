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

    public function testDefaultTimestamp()
    {
        $authentication = new \Akamai\Open\EdgeGrid\Authentication();
        $authentication->setAuth("test", "test", "test");
        $authentication->setHttpMethod("GET");
        $authentication->setPath("/test");
        $authentication->setHost("https://example.org");
        $authentication->createAuthHeader();
        
        $this->assertInstanceOf(
            \Akamai\Open\EdgeGrid\Authentication\Timestamp::CLASS,
            \PHPUnit_Framework_Assert::readAttribute($authentication, 'timestamp')
        );
    }

    public function testDefaultNonce()
    {
        $authentication = new \Akamai\Open\EdgeGrid\Authentication();
        $authentication->setAuth("test", "test", "test");
        $authentication->setHttpMethod("GET");
        $authentication->setPath("/test");
        $authentication->setHost("https://example.org");
        $authentication->createAuthHeader();
        $authentication->setNonce();
        
        $this->assertInstanceOf(
            \Akamai\Open\EdgeGrid\Authentication\Nonce::CLASS,
            \PHPUnit_Framework_Assert::readAttribute($authentication, 'nonce')
        );
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Timestamp is invalid. Too old?
     */
    public function testTimestampTimeout()
    {
        $authentication = new \Akamai\Open\EdgeGrid\Authentication();
        $authentication->setAuth("test", "test", "test");
        $authentication->setHttpMethod("GET");
        $authentication->setPath("/test");
        $authentication->setHost("https://example.org");
            
        $timestamp = new \Akamai\Open\EdgeGrid\Authentication\Timestamp();
        $timestamp->setValidFor('PT0S');
        $authentication->setTimestamp($timestamp);
        sleep(1);
        $authentication->createAuthHeader();
    }
    
    public function testSignHeadersArray()
    {
        $closure = function () {
        
            return $this->canonicalizeHeaders();
        };
        
        $authentication = new \Akamai\Open\EdgeGrid\Authentication();
        $authentication->setAuth("test", "test", "test");
        $authentication->setHttpMethod("GET");
        $authentication->setPath("/test");
        $authentication->setHost("https://example.org");
        $authentication->setHeaders([
            'X-Test-1' => ["Value1", "value2"]
        ]);
        
        $authentication->setHeadersToSign(['X-Test-1']);
        $tester = $closure->bindTo($authentication, $authentication);
        $this->assertEquals("x-test-1:Value1", $tester());

        $authentication->setHeaders([
            'X-Test-1' => []
        ]);
        $authentication->setHeadersToSign(['X-Test-1']);
        $this->assertEmpty($tester());
    }
    
    public function testSetHost()
    {
        $authentication = new \Akamai\Open\EdgeGrid\Authentication();
        $authentication->setHost("example.org");
        $this->assertEquals(
            "example.org",
            \PHPUnit_Framework_Assert::readAttribute($authentication, 'host')
        );

        $this->assertNull(\PHPUnit_Framework_Assert::readAttribute($authentication, 'path'));
        $this->assertArrayNotHasKey('query', \PHPUnit_Framework_Assert::readAttribute($authentication, 'config'));

        $authentication = new \Akamai\Open\EdgeGrid\Authentication();
        $authentication->setHost("http://example.com");
        $this->assertEquals(
            "example.com",
            \PHPUnit_Framework_Assert::readAttribute($authentication, 'host')
        );

        $this->assertNull(\PHPUnit_Framework_Assert::readAttribute($authentication, 'path'));
        $this->assertArrayNotHasKey('query', \PHPUnit_Framework_Assert::readAttribute($authentication, 'config'));
    }
    
    public function testSetHostWithPath()
    {
        $authentication = new \Akamai\Open\EdgeGrid\Authentication();

        $authentication->setHost("example.net/path");
        $this->assertEquals(
            "example.net",
            \PHPUnit_Framework_Assert::readAttribute($authentication, 'host')
        );
        $this->assertEquals('/path', \PHPUnit_Framework_Assert::readAttribute($authentication, 'path'));
        $this->assertArrayNotHasKey('query', \PHPUnit_Framework_Assert::readAttribute($authentication, 'config'));

        $authentication->setHost("http://example.org/newpath");
        $this->assertEquals(
            "example.org",
            \PHPUnit_Framework_Assert::readAttribute($authentication, 'host')
        );
        $this->assertEquals('/newpath', \PHPUnit_Framework_Assert::readAttribute($authentication, 'path'));
        $this->assertArrayNotHasKey('query', \PHPUnit_Framework_Assert::readAttribute($authentication, 'config'));
    }
    
    public function testSetHostWithQuery()
    {
        $authentication = new \Akamai\Open\EdgeGrid\Authentication();
        
        $authentication->setHost("example.net/path?query=string");
        $this->assertEquals(
            "example.net",
            \PHPUnit_Framework_Assert::readAttribute($authentication, 'host')
        );
        $this->assertEquals('/path', \PHPUnit_Framework_Assert::readAttribute($authentication, 'path'));
        $this->assertArrayHasKey('query', \PHPUnit_Framework_Assert::readAttribute($authentication, 'config'));
        $this->assertEquals(
            'query=string',
            \PHPUnit_Framework_Assert::readAttribute($authentication, 'config')['query']
        );

        $authentication->setHost("http://example.org/newpath?query=newstring");
        $this->assertEquals(
            "example.org",
            \PHPUnit_Framework_Assert::readAttribute($authentication, 'host')
        );
        $this->assertEquals('/newpath', \PHPUnit_Framework_Assert::readAttribute($authentication, 'path'));
        $this->assertArrayHasKey('query', \PHPUnit_Framework_Assert::readAttribute($authentication, 'config'));
        $this->assertEquals(
            'query=newstring',
            \PHPUnit_Framework_Assert::readAttribute($authentication, 'config')['query']
        );

        $authentication->setHost("http://example.org?query=newstring");
        $this->assertEquals(
            "example.org",
            \PHPUnit_Framework_Assert::readAttribute($authentication, 'host')
        );
        $this->assertEquals('/', \PHPUnit_Framework_Assert::readAttribute($authentication, 'path'));
        $this->assertArrayHasKey('query', \PHPUnit_Framework_Assert::readAttribute($authentication, 'config'));
        $this->assertEquals(
            'query=newstring',
            \PHPUnit_Framework_Assert::readAttribute($authentication, 'config')['query']
        );
        
        $authentication->setHost("http://example.net/?query=string");
        $this->assertEquals(
            "example.net",
            \PHPUnit_Framework_Assert::readAttribute($authentication, 'host')
        );
        $this->assertEquals('/', \PHPUnit_Framework_Assert::readAttribute($authentication, 'path'));
        $this->assertArrayHasKey('query', \PHPUnit_Framework_Assert::readAttribute($authentication, 'config'));
        $this->assertEquals(
            'query=string',
            \PHPUnit_Framework_Assert::readAttribute($authentication, 'config')['query']
        );
    }
    
    public function testSetPath()
    {
        $authentication = new \Akamai\Open\EdgeGrid\Authentication();

        $authentication->setPath("/path");
        $this->assertEmpty(
            \PHPUnit_Framework_Assert::readAttribute($authentication, 'host')
        );
        $this->assertEquals('/path', \PHPUnit_Framework_Assert::readAttribute($authentication, 'path'));
        $this->assertArrayNotHasKey('query', \PHPUnit_Framework_Assert::readAttribute($authentication, 'config'));

        $authentication = new \Akamai\Open\EdgeGrid\Authentication();
        $authentication->setPath("https://example.net/path");
        $this->assertEquals(
            "example.net",
            \PHPUnit_Framework_Assert::readAttribute($authentication, 'host')
        );
        $this->assertEquals('/path', \PHPUnit_Framework_Assert::readAttribute($authentication, 'path'));
        $this->assertArrayNotHasKey('query', \PHPUnit_Framework_Assert::readAttribute($authentication, 'config'));

        $authentication = new \Akamai\Open\EdgeGrid\Authentication();
        $authentication->setPath("/newpath?query=string");
        $this->assertEmpty(
            \PHPUnit_Framework_Assert::readAttribute($authentication, 'host')
        );
        $this->assertEquals('/newpath', \PHPUnit_Framework_Assert::readAttribute($authentication, 'path'));
        $this->assertArrayHasKey('query', \PHPUnit_Framework_Assert::readAttribute($authentication, 'config'));
        $this->assertEquals(
            'query=string',
            \PHPUnit_Framework_Assert::readAttribute($authentication, 'config')['query']
        );

        $authentication = new \Akamai\Open\EdgeGrid\Authentication();
        $authentication->setPath("https://example.net/path?query=newstring");
        $this->assertEquals(
            "example.net",
            \PHPUnit_Framework_Assert::readAttribute($authentication, 'host')
        );
        $this->assertEquals('/path', \PHPUnit_Framework_Assert::readAttribute($authentication, 'path'));
        $this->assertArrayHasKey('query', \PHPUnit_Framework_Assert::readAttribute($authentication, 'config'));
        $this->assertEquals(
            'query=newstring',
            \PHPUnit_Framework_Assert::readAttribute($authentication, 'config')['query']
        );
    }

    /**
     * @dataProvider createFromEdgeRcProvider
     */
    public function testCreateFromEdgeRcDefault($section, $file)
    {
        $_SERVER['HOME'] = __DIR__ .'/edgerc';
        $authentication = \Akamai\Open\EdgeGrid\Authentication::createFromEdgeRcFile($section, $file);

        $this->assertInstanceOf(\Akamai\Open\EdgeGrid\Authentication::CLASS, $authentication);
        $this->assertEquals(
            [
                'client_token' => "akab-client-token-xxx-xxxxxxxxxxxxxxxx",
                'client_secret' => "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=",
                'access_token' => "akab-access-token-xxx-xxxxxxxxxxxxxxxx"
            ],
            \PHPUnit_Framework_Assert::readAttribute($authentication, 'auth')
        );
        $this->assertEquals(
            'akaa-baseurl-xxxxxxxxxxx-xxxxxxxxxxxxx.luna.akamaiapis.net',
            \PHPUnit_Framework_Assert::readAttribute($authentication, 'host')
        );
        $this->assertEquals(2048, \PHPUnit_Framework_Assert::readAttribute($authentication, 'max_body_size'));
    }

    public function testCreateFromEdgeRcUseCwd()
    {
        $_SERVER['HOME'] = "/non-existant";
        $unlink = false;
        if (!file_exists('./.edgerc')) {
            touch('./.edgerc');
            $unlink = true;
        }
        
        try {
            $auth = \Akamai\Open\EdgeGrid\Authentication::createFromEdgeRcFile();
            $this->assertInstanceOf(\Akamai\Open\EdgeGrid\Authentication::CLASS, $auth);
        } catch (\Exception $e) {
            $this->assertEquals('Section "default" does not exist!', $e->getMessage());
        } finally {
            if ($unlink) {
                unlink('./.edgerc');
            }
        }
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage File "/non-existant/.edgerc" does not exist!
     */
    public function testCreateFromEdgeRcNonExistant()
    {
        $auth = \Akamai\Open\EdgeGrid\Authentication::createFromEdgeRcFile(null, "/non-existant/.edgerc");
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Unable to read .edgerc file!
     */
    public function testCreateFromEdgeRcNonReadable()
    {
        $filename = tempnam(sys_get_temp_dir(), '.');
        touch(tempnam(sys_get_temp_dir(), '.'));
        chmod($filename, 0000);
        
        try {
            $auth = \Akamai\Open\EdgeGrid\Authentication::createFromEdgeRcFile(null, $filename);
        } finally {
            chmod($filename, 0777);
            unlink($filename);
        }
    }
    
    public function testCreateFromEdgeRcColons()
    {
        $file = __DIR__ . '/edgerc/.edgerc.invalid';
        $authentication = \Akamai\Open\EdgeGrid\Authentication::createFromEdgeRcFile(null, $file);

        $this->assertInstanceOf(\Akamai\Open\EdgeGrid\Authentication::CLASS, $authentication);
        $this->assertEquals(
            [
                'client_token' => "akab-client-token-xxx-xxxxxxxxxxxxxxxx",
                'client_secret' => "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=",
                'access_token' => "akab-access-token-xxx-xxxxxxxxxxxxxxxx"
            ],
            \PHPUnit_Framework_Assert::readAttribute($authentication, 'auth')
        );
        $this->assertEquals(
            'akaa-baseurl-xxxxxxxxxxx-xxxxxxxxxxxxx.luna.akamaiapis.net',
            \PHPUnit_Framework_Assert::readAttribute($authentication, 'host')
        );
        $this->assertEquals(2048, \PHPUnit_Framework_Assert::readAttribute($authentication, 'max_body_size'));
    }
    
    public function testCreateFromEdgeRcColonsWithSpaces()
    {
        $file = __DIR__ . '/edgerc/.edgerc.invalid-spaces';
        $authentication = \Akamai\Open\EdgeGrid\Authentication::createFromEdgeRcFile(null, $file);

        $this->assertInstanceOf(\Akamai\Open\EdgeGrid\Authentication::CLASS, $authentication);
        $this->assertEquals(
            [
                'client_token' => "akab-client-token-xxx-xxxxxxxxxxxxxxxx",
                'client_secret' => "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=",
                'access_token' => "akab-access-token-xxx-xxxxxxxxxxxxxxxx"
            ],
            \PHPUnit_Framework_Assert::readAttribute($authentication, 'auth')
        );
        $this->assertEquals(
            'akaa-baseurl-xxxxxxxxxxx-xxxxxxxxxxxxx.luna.akamaiapis.net',
            \PHPUnit_Framework_Assert::readAttribute($authentication, 'host')
        );
        $this->assertEquals(2048, \PHPUnit_Framework_Assert::readAttribute($authentication, 'max_body_size'));
    }
    
    public function testSetConfig()
    {
        $authentication = new \Akamai\Open\EdgeGrid\Authentication();
        
        $config = ['test' => 'value'];
        $authentication->setConfig($config);

        $this->assertEquals($config, \PHPUnit_Framework_Assert::readAttribute($authentication, 'config'));
        
        $authentication = new \Akamai\Open\EdgeGrid\Authentication();
        $authentication->setQuery('query=string');
        $authentication->setConfig($config);
        
        $config['query'] = 'query=string';
        $this->assertEquals($config, \PHPUnit_Framework_Assert::readAttribute($authentication, 'config'));
    }
    
    public function createFromEdgeRcProvider()
    {
        $clientTest = new \Akamai\Open\EdgeGrid\Tests\ClientTest();
        return $clientTest->createFromEdgeRcProvider();
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
