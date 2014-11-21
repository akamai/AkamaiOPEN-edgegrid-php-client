<?php
/**
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

/**
 * Test for EdgeGrid
 *
 * @author Hideki Okamoto <hokamoto@akamai.com>
 * @since PHP 5.4
 * @version 1.0
 */
class EdgeGridTest extends PHPUnit_Framework_TestCase
{
    public function testMakeAuthHeader()
    {
        $testdata = json_decode(file_get_contents(dirname(__FILE__) . '/testdata.json'), true);

        $eg = new \Akamai\EdgeGrid(
            $testdata['client_token'],
            $testdata['client_secret'],
            parse_url($testdata['base_url'])['host'],
            $testdata['access_token'],
            $testdata['headers_to_sign'],
            $testdata['max_body']
        );

        foreach ($testdata['tests'] as $test) {
            $request = $test['request'];

            $tested_headers = [];
            if (isset($request['headers'])) {
                foreach ($request['headers'] as $header) {
                    $tested_headers += $header;
                }
            }

            $this->assertEquals(
                $test['expectedAuthorization'],
                $eg->makeAuthHeader(
                    $request['method'],
                    $request['path'],
                    $tested_headers,
                    $request['data'],
                    $testdata['timestamp'],
                    $testdata['nonce']
                ),
                $test['testName']
            );
        }
    }

    public function testMakeNonce()
    {
        $nonces = [];
        for ($i = 0; $i < 100; $i++) {
            array_push($nonces, \Akamai\EdgeGrid::makeNonce());
        }

        $this->assertEquals(100, count(array_unique($nonces)));
    }

    public function testGetEdgeGridTimestamp() {
        $this->assertRegExp('/\A\d{4}[0-1][0-9][0-3][0-9]T[0-2][0-9]:[0-5][0-9]:[0-5][0-9][+]0000\z/', \Akamai\EdgeGrid::getEdgeGridTimestamp());
    }
}
