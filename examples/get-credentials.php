<?php
// This example returns a list of your API client credentials.
//
// To run this example:
//
// 1. Specify the section header of the set of credentials to use.
//
// The defaults here expect the .edgerc at your home directory and use the credentials under the heading of default.
//
// 2. Open a Terminal or shell instance and run "php examples/get-credentials.php".
//
// A successful call returns your credentials grouped by credentialId.
//
// For more information on the call used in this example, see https://techdocs.akamai.com/iam-api/reference/get-self-credentials.

require "vendor/autoload.php";

$client = \Akamai\Open\EdgeGrid\Client::createFromEdgeRcFile('default');

$headers = [
  'Accept' => 'application/json',
];

$response = $client->get('/identity-management/v3/api-clients/self/credentials', $headers);

echo $response->getBody();
?>