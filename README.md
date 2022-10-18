# akamai-open/edgegrid-client

[Akamai EdgeGrid Authentication](https://techdocs.akamai.com/developer/docs/set-up-authentication-credentials) for PHP

This library requires PHP 8+ and implements the Akamai EdgeGrid Authentication scheme on top of [Guzzle](https://github.com/guzzle/guzzle) as both a drop-in replacement client and middleware.

## Installation

To install, use [`composer`](http://getcomposer.org):

```sh
$ composer require akamai-open/edgegrid-client
```

### Alternative installation method

Download the PHAR file from the [releases](https://github.com/akamai/AkamaiOPEN-edgegrid-php/releases) page and include it inside your code:

    ```php
    include 'akamai-open-edgegrid-auth.phar';

    // Library is ready to use
    ```

## Use

The `Akamai\Open\EdgeGrid\Client` extends `\GuzzleHttp\Client` as a drop-in replacement. It works transparently to sign API requests without changing other ways you use Guzzle.

To use the client, call `\Akamai\Open\EdgeGrid\Client->setAuth()` or provide an instance of `\Akamai\Open\EdgeGrid\Authentication` to the constructor prior to making a request to an API.

```php
$client = new Akamai\Open\EdgeGrid\Client([
  'base_uri' => 'https://akab-h05tnam3wl42son7nktnlnnx-kbob3i3v.luna.akamaiapis.net'
]);

$client->setAuth($client_token, $client_secret, $access_token);

// use $client just as you would \Guzzle\Http\Client
$response = $client->get('/identity-management/v3/user-profile');
```

## Authentication

To generate your credentials, see [Create authentication credentials](https://techdocs.akamai.com/developer/docs/set-up-authentication-credentials).

We recommend using a local `.edgerc` authentication file. Place your credentials under a heading of `[default]` at your local home directory or the home directory of a web-server user.

```
[default]
client_secret = C113nt53KR3TN6N90yVuAgICxIRwsObLi0E67/N8eRN=
host = akab-h05tnam3wl42son7nktnlnnx-kbob3i3v.luna.akamaiapis.net
access_token = akab-acc35t0k3nodujqunph3w7hzp7-gtm6ij
client_token = akab-c113ntt0k3n4qtari252bfxxbsl-yvsdj
```

You can call your `.edgerc` file one of two ways:

*  Use the factory method `\Akamai\Open\EdgeGrid\Client::createFromEdgeRcFile()`.

    ```php
    $client = \Akamai\Open\EdgeGrid\Client::createFromEdgeRcFile();

    // use $client just as you would \Guzzle\Http\Client
    $response = $client->get('/identity-management/v3/user-profile');
    ```

* Specify a credentials section and/or `.edgerc` location:

    ```php
    $client = \Akamai\Open\EdgeGrid\Client::createFromEdgeRcFile('example', '../config/.edgerc');

    // use $client just as you would \Guzzle\Http\Client
    $response = $client->get('/identity-management/v3/user-profile');
    ```

## Command line interface

This library provides a command line interface (CLI) with a limited set of capabilities that mimic [httpie](http://httpie.org).

### Install

Install the CLI with composer `vendor/bin/http` or execute the PHAR file.

```sh
# Composer installed
$ ./vendor/bin/http --help

# For Windows
> php ./vendor/bin/http --help

# PHAR download
php akamai-open-edgegrid-client.phar --help
```

### Arguments

You can set authentication and specify an HTTP method (case insensitive), its headers, and any JSON body fields.

> **Note:** Our CLI shows all HTTP and body data. JSON is formated.

|Argument|Description|
|---|---|
|`--auth-type={edgegrid,basic,digest}`|Set the authentication type. The default is `none`.|
|`--auth user:` or `--a user:`|Set the `.edgerc` section to use. Unlike `httpie-edgegrid`, the colon (`:`) is optional.|
|`Header-Name:value`|Headers and values are colon (`:`) separated.|
|`jsonKey=value`|Sends `{"jsonKey": "value"}` in the `POST` or `PUT` body. This will also automatically set the `Content-Type` and `Accept` headers to `application/json`.|
|`jsonKey:=[1,2,3]`|Allows you to specify raw JSON data, sending `{"jsonKey": [1, 2, 3]}` in the body.|

### Limitations

* You cannot send `multipart/mime` (file upload) data.
* Client certs are not supported.
* Server certs cannot be verified.
* Output cannot be customized.
* Responses are not syntax highlighted.

## Guzzle Middleware

This package provides three different middleware handlers you can add transparently when using the `Client`, to a standard `\GuzzleHttp\Client` or as a handler.

* The `\Akamai\Open\EdgeGrid\Handler\Authentication` for transparent API request signing.
* The `\Akamai\Open\EdgeGrid\Handler\Verbose` for output (or log) responses.
* The `\Akamai\Open\EdgeGrid\Handler\Debug` for output (or log) errors.

### Transparent Use

|Handler|Call|
|---|---|
|`Authentication`|`Client->setAuthentication()` or pass in an instance of `\Akamai\EdgeGrid\Authentication` to `Client->__construct()`.|
|`Verbose`|`Client->setInstanceVerbose()` or `Client::setVerbose()` passing in on of `true|resource|[resource output, resource error]`. The default is `[STDOUT, STDERR]`.|
|`Debug`|`Client->setInstanceDebug()`, `Client::setDebug()`, or set the `debug` config option with `true|resource`. The default is `STDERR`.|

### Middleware

<table>
  <thead>
    <tr>
      <th>Handler</th>
      <th>Example</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Authentication</td>
      <td>
<pre lang="php">
// Create the Authentication Handler
$auth = \Akamai\Open\EdgeGrid\Handler\Authentication::createFromEdgeRcFile();
// or:
$auth = new \Akamai\Open\EdgeGrid\Handler\Authentication;
$auth-&gt;setAuth($client_token, $client_secret, $access_token);

// Create the handler stack
$handlerStack = \GuzzleHttp\HandlerStack::create();

// Add the Auth handler to the stack
$handlerStack-&gt;push($auth);

// Add the handler to a regular \GuzzleHttp\Client
$guzzle = new \GuzzleHttp\Client([
    "handler" =&gt; $handlerStack
]);
</pre>
      </td>
    </tr>
    <tr>
      <td>Verbose</td>
      <td>
<pre lang="php">
// Create the handler stack
$handlerStack = HandlerStack::create();

// Add the Auth handler to the stack
$handlerStack-&gt;push(new \Akamai\Open\EdgeGrid\Handler\Verbose());

// Add the handler to a regular \GuzzleHttp\Client
$guzzle = new \GuzzleHttp\Client([
    "handler" =&gt; $handlerStack
]);
</pre>
      </td>
    </tr>
    <tr>
      <td>Debug</td>
      <td>
<pre lang="php">
// Create the handler stack
$handlerStack = HandlerStack::create();

// Add the Auth handler to the stack
$handlerStack-&gt;push(new \Akamai\Open\EdgeGrid\Handler\Debug());

// Add the handler to a regular \GuzzleHttp\Client
$guzzle = new \GuzzleHttp\Client([
    "handler" =&gt; $handlerStack
]);
</pre>
      </td>
    </tr>
  </tbody>
</table>

## License

Copyright © 2022 Akamai Technologies, Inc. All rights reserved

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at <http://www.apache.org/licenses/LICENSE-2.0>.

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
