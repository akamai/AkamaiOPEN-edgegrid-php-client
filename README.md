# akamai-open/edgegrid-client

[![License](https://img.shields.io/github/license/akamai-open/edgegrid-auth-php.png)](https://github.com/akamai-open/edgegrid-auth-php/blob/master/LICENSE) [![Build Status](https://travis-ci.org/akamai-open/edgegrid-auth-php.svg?branch=master)](https://travis-ci.org/akamai-open/edgegrid-auth-php) [![Code Coverage](https://scrutinizer-ci.com/g/akamai-open/edgegrid-auth-php/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/akamai-open/edgegrid-auth-php/?branch=master)

[Akamai {OPEN} EdgeGrid Authentication] for PHP

[Akamai {OPEN} EdgeGrid Authentication]: https://developer.akamai.com/introduction/Client_Auth.html

This library implements the Akamai {OPEN} EdgeGrid Authentication scheme on top of [Guzzle](https://github.com/guzzle/guzzle), as both a drop-in replacement client, and middleware.

For more information visit the [Akamai {OPEN} Developer Community](https://developer.akamai.com).

## Installation

This library requires PHP 5.5+, or HHVM 3.5+.

To install use [`composer`](http://getcomposer.org):

```sh
$ composer require akamai-open/edgegrid-client
```

## Client Usage

The `Akamai\Open\EdgeGrid\Client` extends `\GuzzleHttp\Client` and transparently enables you to sign API requests,
without interfering with other usage - this makes it a drop-in replacement, with the exception that you _must_ call
`\Akamai\Open\EdgeGrid\Client->setAuth()` (or provide an instance of `\Akamai\Open\EdgeGrid\Authentication` to the
constructor) prior to making a request to an API.

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

## Guzzle Middleware

This package provides three different middleware handlers:

- `\Akamai\Open\EdgeGrid\Handler\Authentication` - provides transparent API request signing
- `\Akamai\Open\EdgeGrid\Handler\Verbose` - easily output (or log) responses
- `\Akamai\Open\EdgeGrid\Handler\Debug` - easily output (or log) errors

All three can be added transparently when using the `Client`, or added to a standard `\GuzzleHttp\Client`, or by adding them as a handler.

### Transparent Usage

To enable `Authentication` call `Client->setAuthentication()`, or pass in an instance of `\Akamai\EdgeGrid\Authentication`
to `Client->__construct()`.

To enable `Verbose` call `Client->setInstanceVerbose()` or `Client::setVerbose()` passing in on of `true|resource|[resource output, resource error]. Defaults to `[STDOUT, STDERR]`.

To enable `Debug` call `Client->setInstanceDebug()`, `Client::setDebug()`, or set the `debug` config option with `true|resource`. Defaults to `STDERR`.

### Middleware

#### Authentication Handler

```php
// Create the Authentication Handler
$auth = \Akamai\Open\EdgeGrid\Handler\Authentication::createFromEdgeRcFile();
// or:
$auth = new \Akamai\Open\EdgeGrid\Handler\Authentication;
$auth->setAuth($client_token, $client_secret, $access_token);

// Create the handler stack
$handlerStack = HandlerStack::create();

// Add the Auth handler to the stack
$handlerStack->push($auth);

// Add the handler to a regular \GuzzleHttp\Client
$guzzle = new \GuzzleHttp\Client([
    "handler" => $handlerStack
]);
```

#### Verbose Handler

```php
// Create the handler stack
$handlerStack = HandlerStack::create();

// Add the Auth handler to the stack
$handlerStack->push(new \Akamai\Open\EdgeGrid\Handler\Verbose());

// Add the handler to a regular \GuzzleHttp\Client
$guzzle = new \GuzzleHttp\Client([
    "handler" => $handlerStack
]);
```

### Debug Handler

```php
// Create the handler stack
$handlerStack = HandlerStack::create();

// Add the Auth handler to the stack
$handlerStack->push(new \Akamai\Open\EdgeGrid\Handler\Debug());

// Add the handler to a regular \GuzzleHttp\Client
$guzzle = new \GuzzleHttp\Client([
    "handler" => $handlerStack
]);
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
