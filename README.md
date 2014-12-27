edgegrid-php
=============
[Akamai {OPEN} EdgeGrid Authentication] for PHP

[Akamai {OPEN} EdgeGrid Authentication]: https://developer.akamai.com/introduction/Client_Auth.html

This library implements the Akamai {OPEN} EdgeGrid Authentication scheme

For more information visit the [Akamai {OPEN} Developer Community](https://developer.akamai.com).

Installation
------------

This library requires PHP 5.4 or later.  

Place the EdgeGrid.php file with the rest of your PHP libraries (or in the same directory as your other scripts)

Example scripts can be found in the api-kickstart repository.

This library requires that your credentials be in an .edgerc file in your home directory (or the script can be
instructed to use a different file).  The format for this file is as follows:

[default]
client_secret = xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=
host = xxxxx.luna.akamaiapis.net/
access_token = xxxxx
client_token = xxxxx

Credentials can be generated using information on the developer site at:
https://developer.akamai.com/introduction/Prov_Creds.html

Once the credentials are set, export them via the export button on the upper right hand side of the screen in the luna portal 
and format them as above in your .edgerc file.

Usage
-----

```php
<?php

require_once ('EdgeGrid.php');
$client = new \Akamai\EdgeGrid(false, 'test', 'test'); # insert the section of the .edgerc file for the calls here.

$client->path = '/diagnostic-tools/v1/locations';
$client->headers = array('X-testheader' => 'testdata');
$response = $client->request();
if($response['error']){
        var_dump($response['error']);
} else {
        var_dump($response['body']);
}

$response = $client->get('/billing-usage/v1/reportSources', []);
echo $response->body;
```

Author
------

Michael Coury <consulting@vorien.com>

License
-------

Copyright 2014 Akamai Technologies, Inc.  All rights reserved.

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
