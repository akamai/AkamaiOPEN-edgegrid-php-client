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

class OptionsHandlerTest extends \PHPUnit_Framework_TestCase
{
    public function testGetBodyOption()
    {
        $optionsHandler = new \Akamai\Open\EdgeGrid\Client\OptionsHandler();
        $optionsHandler->setOptions(['body' => 'test']);
        $optionsHandler->setHost('example.org');
        $options = $optionsHandler->getOptions();
        
        $this->assertArrayHasKey('body', $options);
        $this->assertEquals($options['body'], 'test');

        $optionsHandler = new \Akamai\Open\EdgeGrid\Client\OptionsHandler();
        $optionsHandler->setOptions(['form_params' => ['form' => 'value']]);
        $optionsHandler->setHost('example.org');
        $options = $optionsHandler->getOptions();

        $this->assertArrayHasKey('body', $options);
        $this->assertEquals($options['body'], 'form=value');
        
        $optionsHandler = new \Akamai\Open\EdgeGrid\Client\OptionsHandler();
        $optionsHandler->setOptions(['form_params' => 'form=newvalue']);
        $optionsHandler->setHost('example.org');
        $options = $optionsHandler->getOptions();

        $this->assertArrayHasKey('body', $options);
        $this->assertEquals($options['body'], 'form=newvalue');
    }
    
    public function testGetAuthenticatedOptions()
    {
        $_SERVER['HOME'] = __DIR__ . '/../edgerc';
        
        $authentication = \Akamai\Open\EdgeGrid\Authentication::createFromEdgeRcFile();
        $optionsHandler = new \Akamai\Open\EdgeGrid\Client\OptionsHandler($authentication);
        $optionsHandler->setHost('https://test-akamaiapis.net');
        $options = $optionsHandler->getOptions();
        
        $this->assertArrayHasKey('headers', $options);
        $this->assertArrayHasKey('Authorization', $options['headers']);
    }
    
    public function testGetHostOptionHostHeader()
    {
        $optionsHandler = new \Akamai\Open\EdgeGrid\Client\OptionsHandler();
        $optionsHandler->setOptions(['headers' => ['Host' => 'http://example.org']]);
        $options = $optionsHandler->getOptions();
        
        $this->assertEquals('example.org', \PHPUnit_Framework_Assert::readAttribute($optionsHandler, 'host'));
        $this->assertEquals('http', \PHPUnit_Framework_Assert::readAttribute($optionsHandler, 'scheme'));
        $this->assertArrayHasKey('base_uri', $options);
        $this->assertEquals('http://example.org', $options['base_uri']);
    }
    
    public function testGetHostOptionBaseUri()
    {
        $optionsHandler = new \Akamai\Open\EdgeGrid\Client\OptionsHandler();
        $optionsHandler->setOptions(['base_uri' => 'http://example.org']);
        $options = $optionsHandler->getOptions();
        
        $this->assertEquals('example.org', \PHPUnit_Framework_Assert::readAttribute($optionsHandler, 'host'));
        $this->assertEquals('http', \PHPUnit_Framework_Assert::readAttribute($optionsHandler, 'scheme'));
        $this->assertArrayHasKey('base_uri', $options);
        $this->assertEquals('http://example.org', $options['base_uri']);
    }
    
    public function testGetHostOptionPath()
    {
        $optionsHandler = new \Akamai\Open\EdgeGrid\Client\OptionsHandler();
        $optionsHandler->setPath('http://example.org/test');
        $options = $optionsHandler->getOptions();
        
        $this->assertEquals('example.org', \PHPUnit_Framework_Assert::readAttribute($optionsHandler, 'host'));
        $this->assertEquals('http', \PHPUnit_Framework_Assert::readAttribute($optionsHandler, 'scheme'));
        $this->assertArrayHasKey('base_uri', $options);
        $this->assertEquals('http://example.org', $options['base_uri']);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage No Host set
     */
    public function testGetHostOptionNoHost()
    {
        $optionsHandler = new \Akamai\Open\EdgeGrid\Client\OptionsHandler();
        $options = $optionsHandler->getOptions();
    }
    
    public function testGetScheme()
    {
        $optionsHandler = new \Akamai\Open\EdgeGrid\Client\OptionsHandler();
        $optionsHandler->setScheme('http');
        $this->assertEquals('http', $optionsHandler->getScheme());
    }
    
    public function testSetQuery()
    {
        $optionsHandler = new \Akamai\Open\EdgeGrid\Client\OptionsHandler();
        $optionsHandler->setQuery(['query' => 'string']);
        $this->assertEquals(['query' => 'string'], \PHPUnit_Framework_Assert::readAttribute($optionsHandler, 'query'));
    }
    
    public function testGetQueryOption()
    {
        $optionsHandler = new \Akamai\Open\EdgeGrid\Client\OptionsHandler();
        $optionsHandler->setHost('example.org');
        $optionsHandler->setQuery(['query' => 'string']);
        $optionsHandler->setOptions(['query' => ['another' => 'value']]);
        $options = $optionsHandler->getOptions();

        parse_str($options['query'], $query);
        ksort($query);
        $this->assertEquals(['another' => 'value', 'query' => 'string'], $query);
    }
}
