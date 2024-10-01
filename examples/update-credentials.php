<?php
// This example updates the credentials from the create credentials example.
//
// To run this example:
//
// 1. Specify the section header of the set of credentials to use.
//
// The defaults here expect the `.edgerc` at your home directory and use the credentials under the heading of `default`.
//
// 2. Add the `credentialId` for the set of credentials created using the create example as a path parameter.
//
// 3. Edit the `expiresOn` date to today's date. Optionally, you can change the `description` value.
//
// 4. Open a Terminal or shell instance and run "php examples/update-credentials.php".
//
// A successful call returns an object with modified credentials.
//
// For more information on the call used in this example, see https://techdocs.akamai.com/iam-api/reference/put-self-credential.

require "vendor/autoload.php";

$client = \Akamai\Open\EdgeGrid\Client::createFromEdgeRcFile('default');

$headers = [
    'Content-Type' => 'application/json',
    'Accept' => 'application/json',
  ];

$body = [
  'json' => [
    'status' => 'INACTIVE',
    'description' => 'Update this credential',
    'expiresOn' => '2024-12-11T23:06:59.000Z'
  ]
];

$response = $client->put('/identity-management/v3/api-clients/self/credentials/123456', $body, $headers);

echo $response->getBody();