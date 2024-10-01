<?php
// This example deletes your API client credentials.
//
// To run this example:
//
// 1. Specify the section header of the set of credentials to use.
//
// The defaults here expect the `.edgerc` at your home directory and use the credentials under the heading of `default`.
//
// 2. Add the `credentialId` from the update example to the path. You can only delete inactive credentials. Sending the request on an active set will return a 400. Use the update credentials example for deactivation.
//
// **Important:** Don't use your actual credentials for this operation. Otherwise, you'll block your access to the Akamai APIs.
//
// 3. Open a Terminal or shell instance and run "php examples/delete-credentials.php".
//
// A successful call returns an empty response body.
//
// For more information on the call used in this example, see https://techdocs.akamai.com/iam-api/reference/delete-self-credential.

require "vendor/autoload.php";

$client = \Akamai\Open\EdgeGrid\Client::createFromEdgeRcFile('default');

$headers = [
  'Accept' => 'application/json',
];

$response = $client->delete('/identity-management/v3/api-clients/self/credentials/123456', $headers);

echo $response->getBody();