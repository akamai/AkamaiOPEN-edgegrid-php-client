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
namespace Akamai\Open\EdgeGrid\Tests\Client\Authentication;

class NonceTest extends \PHPUnit_Framework_TestCase
{
    public function testMakeNonce()
    {
        $nonce = new \Akamai\Open\EdgeGrid\Authentication\Nonce();

        $nonces = [];
        for ($i = 0; $i < 100; $i++) {
            $nonces[] = (string) $nonce;
        }

        $this->assertEquals(100, count(array_unique($nonces)));
    }

    public function testMakeNonceRandomBytes()
    {
        if (!function_exists('random_bytes')) {
            include __DIR__ . '/../random_bytes.php';
        }

        $nonce = new \Akamai\Open\EdgeGrid\Authentication\Nonce();
        $closure = function() {
            return $this->function;
        };
        $tester = $closure->bindTo($nonce, $nonce);

        $this->assertEquals('random_bytes', $tester());
    }
}
