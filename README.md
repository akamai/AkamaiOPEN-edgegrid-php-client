# akamai-open/edgegrid-client

[![License](https://img.shields.io/github/license/akamai-open/AkamaiOPEN-edgegrid-php.png)](https://github.com/akamai-open/AkamaiOPEN-edgegrid-php/blob/master/LICENSE) [![Build Status](https://travis-ci.org/akamai-open/AkamaiOPEN-edgegrid-php.svg?branch=master)](https://travis-ci.org/akamai-open/AkamaiOPEN-edgegrid-php) [![Code Coverage](https://scrutinizer-ci.com/g/akamai-open/AkamaiOPEN-edgegrid-php/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/akamai-open/AkamaiOPEN-edgegrid-php/?branch=master)

[Akamai {OPEN} EdgeGrid Authentication] for PHP

[Akamai {OPEN} EdgeGrid Authentication]: https://developer.akamai.com/introduction/Client_Auth.html

This library implements the Akamai {OPEN} EdgeGrid Authentication scheme on top of [Guzzle](https://github.com/guzzle/guzzle), as both a drop-in replacement client, and middleware.

For more information visit the [Akamai {OPEN} Developer Community](https://developer.akamai.com).

## Installation

This library requires PHP 5.5+, or HHVM 3.5+ to be used with the built-in Guzzle HTTP client.

To install use [`composer`](http://getcomposer.org):

```sh
$ composer require akamai-open/edgegrid-client
```

### Alternative (single file) Install

Alternatively, download the PHAR file from the [releases](https://github.com/akamai-open/AkamaiOPEN-edgegrid-php/releases) page.

To use it, you just include it inside your code:

```php
include 'akamai-open-edgegrid-client.phar';

// Library is ready to use
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

## Command Line Interface

To aid in testing, exploration, and debugging, this library features a CLI that mimics [httpie](http://httpie.org) and provides a limited facsimile of it's capabilities as documented here.

If you install via composer, the CLI tool is available as `vendor/bin/http`, or you can simply execute the PHAR file.

```sh
# Composer installed
$ ./vendor/bin/http --help

# For Windows
> php ./vendor/bin/http --help

# PHAR download
php akamai-open-edgegrid-client.phar --help
```

### Arguments

Arguments are similar to `httpie`:

- `--auth-type={edgegrid,basic,digest}` — Set the authentication type (default: none)
- `--auth user:` or `--a user:` — Set the `.edgerc` section to use. Unlike `httpie-edgegrid` the `:` is optional

You can also specify an HTTP method (`HEAD|GET|POST|PUT|DELETE` - case-insensitive).

Finally, you can easily specify headers and JSON body fields, using the following syntaxes:

- `Header-Name:value` — Headers and values are `:` separated
- `jsonKey=value` — Sends `{"jsonKey": "value"}` in the `POST` or `PUT` body. This will also automatically set the `Content-Type` and `Accept` headers to `application/json`.
- `jsonKey:=[1,2,3]` — Allows you to specify raw JSON data, sending `{"jsonKey": [1, 2, 3]}` in the body.

### Limitations

- You cannot send `multipart/mime` (file upload) data
- Client certs are not supported
- Server certs cannot be verified
- Output cannot be customized, all HTTP and body data (request and response) is shown
- Responses are not syntax highlighted (although JSON is formatted)

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
$handlerStack = \GuzzleHttp\HandlerStack::create();

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

### Using PHP 5.3 (not recommended)

> PHP 5.3 has been EOL since August 14th, 2014, and has **known** security vulnerabilities, therefore we do not recommend using it. However, we understand that many actively supported LTS distributions are still shipping with PHP 5.3, and therefore we are providing the following information.

It is possible to use the request signer on it's own, without Guzzle, making it possible to use with PHP 5.3+. To do so you _cannot_ use the autoloader or the phar release. You must [download the source as `zip` or `tar.gz` file](https://github.com/akamai-open/AkamaiOPEN-edgegrid-php/releases) and include all the files yourself:

```php
require_once 'src/Authentication.php';
require_once 'src/Authentication/Timestamp.php';
require_once 'src/Authentication/Nonce.php';
require_once 'src/Exception/ConfigException.php';
require_once 'src/Exception/SignerException/InvalidSignDataException.php';
```

Once you have done this, you can create the header value by calling the appropriate [`\Akamai\Open\Edgegrid\Authentication::set*()` methods](https://akamai-open.github.io/AkamaiOPEN-edgegrid-php/classes/Akamai_Open_EdgeGrid_Authentication.html#methods). For example, using it with the built-in streams HTTP client might look like the following:

```php
$auth = \Akamai\Open\EdgeGrid\Authentication::createFromEdgeRcFile('default', '/.edgerc');
$auth->setHttpMethod('GET');
$auth->setPath('/diagnostic-tools/v1/locations');

$context = array(
	'http' => array(
		'header' => array(
			'Authorization: ' . $auth->createAuthHeader(),
			'Content-Type: application/json'
		)
	)
);

$context = stream_context_create($context);

$response = json_decode(file_get_contents('https://' . $auth->getHost() . $auth->getPath(), null, $context));
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
