# akamai-open/edgegrid-client

[Akamai {OPEN} EdgeGrid Authentication] for PHP

[Akamai {OPEN} EdgeGrid Authentication]: https://developer.akamai.com/introduction/Client_Auth.html

This library implements the Akamai {OPEN} EdgeGrid Authentication scheme on top of [Guzzle](https://github.com/guzzle/guzzle).

For more information visit the [Akamai {OPEN} Developer Community](https://developer.akamai.com).

## Installation

This library requires PHP 5.5+, or HHVM 3.5+.

To install use [`composer`](http://getcomposer.org):

```sh
$ composer require akamai-open/edgegrid-client
```

## Usage

```php
$client = new Akamai\Open\EdgeGrid\Client([
	'base_uri' => 'https://akaa-baseurl-xxxxxxxxxxx-xxxxxxxxxxxxx.luna.akamaiapis.net'
]);

$client->setAuth($client_token, $client_secret, $access_token);

// use $client just as you would \Guzzle\Http\Client
$response = $client->get('/billing-usage/v1/products');
```

### Using a Credentials File

We recommend using a `.edgerc` credentials file. Credentials can be generated using information on the developer site at: https://developer.akamai.com/introduction/Prov_Creds.html

Your `.edgerc` should look something like this:

```
[default]
client_secret = xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=
host = xxxxx.luna.akamaiapis.net/
access_token = xxxxx
client_token = xxxxx
```

To utilize this use the factory method `\Akamai\Open\EdgeGrid\Client::createFromEdgeRcFile()`.

To create a client using the `default` credentials, in an .edgerc file that exists inside the HOME directory of the web server user, or in the current working directory:

```php
$client = \Akamai\Open\EdgeGrid\Client::createFromEdgeRcFile();

// use $client just as you would \Guzzle\Http\Client
$response = $client->get('/billing-usage/v1/products');
```

Or, specify a credentials section and/or `.edgerc` location:

```php
$client = \Akamai\Open\EdgeGrid\Client::createFromEdgeRcFile('example', '../config/.edgerc');

// use $client just as you would \Guzzle\Http\Client
$response = $client->get('/billing-usage/v1/products');
```

## Author

Davey Shafik <dshafik@akamai.com>

## License

Copyright 2015 Akamai Technologies, Inc.  All rights reserved.

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at <http://www.apache.org/licenses/LICENSE-2.0>.

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
