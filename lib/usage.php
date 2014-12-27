<?php
error_reporting(E_ALL|E_STRICT);
ini_set('display_errors', 1);

require_once ('EdgeGrid.php');
//echo function_exists('curl_version');


//	EdgeGrid
//
//	PARAMETERS
//	@param $verbose						[default]	Display debug information (default: false)
//	@param $section						[default]	Section to use from .edgerc (default: 'default')
//	@param $edgrc_location				[optional]	Path to .edgrc file
//	
//	PUBLIC VARIABLES
//	@public  $path						[required]	Base path
//	@public  $query = array()			[optional]	Array of query parameters
//	@public  $headers = array()			[optional]	Array of headers
//	@public  $method = 'GET'			[default]	Method (default: GET)
//	@public  $protocol = 'https'		[default]	Protocol (default: https)
//	@public  $body = ""					[optional]	Request body
//	@public  $timestamp					[optional]	Timestamp override for testing
//	@public  $nonce						[optional]	Nonce override for testing
//	@public  $timeout = 30				[default]	Request timeout (default: 10s)
//	@public  $verbose = false			[default]	Setting to true shows debug information
//
//	@public request()					Execute a request with the above parameters
//
//
//	EdgeGrid->request()
//	
//	Returns:
//		array(
//			body		Response body  (curl_exec)
//			error		Request errors  (curl_error)
//			header		cURL Header Info  (curl_info)
//		)


/*
 * Test call to locations
 */

var_dump('Test call to locations');
$client = new \Akamai\EdgeGrid();
$client->path = '/diagnostic-tools/v1/locations';
$client->headers = array('X-testheader' => 'testdata');
$response = $client->request();
if($response['error']){
	var_dump($response['error']);
} else {
	var_dump($response['body']);
}


/*
 * Test call to dig
 */

var_dump('Test call to dig');
$client = new \Akamai\EdgeGrid();
$client->path = '/diagnostic-tools/v1/dig';
$client->query = array(
	'hostname' => 'developer.akamai.com',
	'queryType' => 'A',
	'location' => 'Frankfurt, Germany',
);
$response = $client->request();
if($response['error']){
	var_dump($response['error']);
} else {
	var_dump($response['body']);
}


/*
 * Test auth headers
 * Use test/.edgrc
 * The signed headers should match
 */

var_dump('Test auth headers');
$client = new \Akamai\EdgeGrid(false, 'test', 'test');
$client->path = '/testapi/v1/t5';
$client->headers = array(
          "X-Test2" => "t2",
                    "X-Test1" => "t1",
                    "X-Test3" => "t3",
);
$client->timestamp = "20140321T19:34:21+0000";
$client->nonce = "nonce-xx-xxxx-xxxx-xxxx-xxxxxxxxxxxx";
$signed_header = $client->makeAuthHeader();
$expected_signed_header = "EG1-HMAC-SHA256 client_token=akab-client-token-xxx-xxxxxxxxxxxxxxxx;access_token=akab-access-token-xxx-xxxxxxxxxxxxxxxx;timestamp=20140321T19:34:21+0000;nonce=nonce-xx-xxxx-xxxx-xxxx-xxxxxxxxxxxx;signature=dwKaMzxVNPuDSaUAy0ee5RoaP9DIolq1E/CvLc1fBRw=";
var_dump("signed header");
var_dump($signed_header);
var_dump("expected signed header");
var_dump($expected_signed_header);
var_dump("Match: " . ($signed_header == $expected_signed_header ? 'match' : 'no match'));


/*
 * Test call to queues
 */

var_dump('Test call to queues');
$client = new \Akamai\EdgeGrid(false, 'ccu');
$client->path = '/ccu/v2/queues/default';
$response = $client->request();
if($response['error']){
	var_dump("An error has occurred");
	var_dump($response['error']);
} else {
	var_dump($response['body']);
}


/*
 * Test POST
 */

var_dump('Test POST');
$client = new \Akamai\EdgeGrid(false, 'ccu');
$client->path = '/ccu/v2/queues/default';
$client->method = "POST";
$client->body = '{ "objects" : [ "https://developer.akamai.com/stuff/openProgramGA.html" ] }';
$client->headers['Content-Length'] = strlen($client->body);
$client->headers["Content-Type"] = "application/json";
$response = $client->request();
if($response['error']){
	var_dump("An error has occurred");
	var_dump($response['error']);
} else {
	var_dump($response['body']);
}

/*
 * Test PUT
 */

var_dump('Test PUT');
$client = new \Akamai\EdgeGrid(false, 'user');
$client->path = '/user-admin/v1/users/B-C-12YT7E9';
$client->method = "PUT";
$client->body = '{ "phone": "1234567890" }';
$client->headers['Content-Length'] = strlen($client->body);
$client->headers["Content-Type"] = "application/json";
$client->timeout = 60;
//$client->nonce = 'cf9d421a-13ed-42e2-9cb4-2046a837151f';
//$client->timestamp = '20141227T01:29:01+0000';
$response = $client->request();
if($response['error']){
	var_dump("An error has occurred");
	var_dump($response['error']);
} else {
	var_dump($response['body']);
}


/*
 * Create a user via POST
 */

var_dump('Create a user via POST');
$client = new \Akamai\EdgeGrid(false, 'user');
$client->path = '/user-admin/v1/users';
$client->method = "POST";
$client->body = '{
    		"roleAssignments": [
      			{
        		"roleId": 14, 
        		"groupId": 41241
      			}
    		], 
    		"firstName": "Kirsten", 
    		"phone": "8315887563", 
    		"lastName": "Hunter", 
    		"email": "kirsten.hunter@akamai.com"
   	}';
$client->headers['Content-Length'] = strlen($client->body);
$client->headers["Content-Type"] = "application/json";
$response = $client->request();
if($response['error']){
	var_dump("An error has occurred");
	var_dump($response['error']);
} else {
	var_dump($response['body']);
}

/*
 * Delete a user via DELETE
 */

var_dump('Delete a user via DELETE');
$client = new \Akamai\EdgeGrid(false, 'user');
$client->path = '/user-admin/v1/users/B-C-195WEHN';
$client->method = "DELETE";
$client->headers["Content-Type"] = "application/json";
$response = $client->request();
if($response['error']){
	var_dump("An error has occurred");
	var_dump($response['error']);
} else {
	var_dump($response['body']);
}


