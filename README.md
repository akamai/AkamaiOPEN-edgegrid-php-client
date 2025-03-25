# akamai-open/edgegrid-client

[Akamai EdgeGrid Authentication](https://techdocs.akamai.com/developer/docs/set-up-authentication-credentials) for PHP.

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

## Authentication

You can obtain the authentication credentials through an API client. Requests to the API are marked with a timestamp and a signature and are executed immediately.

1. [Create authentication credentials](https://techdocs.akamai.com/developer/docs/set-up-authentication-credentials).

2. Place your credentials in an EdgeGrid file `~/.edgerc`, in the `[default]` section.

    ```
    [default]
    client_secret = C113nt53KR3TN6N90yVuAgICxIRwsObLi0E67/N8eRN=
    host = akab-h05tnam3wl42son7nktnlnnx-kbob3i3v.luna.akamaiapis.net
    access_token = akab-acc35t0k3nodujqunph3w7hzp7-gtm6ij
    client_token = akab-c113ntt0k3n4qtari252bfxxbsl-yvsdj
    ```

3. Use your local `.edgerc` by providing credentials' section header and the path to your resource file. You can call your `.edgerc` file using the `\Akamai\Open\EdgeGrid\Client::createFromEdgeRcFile()` method.

    The location of your `.edgerc` file is optional, as it defaults to `~/.edgerc`.

    ```php
    $client = \Akamai\Open\EdgeGrid\Client::createFromEdgeRcFile('{credentials_section_name}', '{path/to/.edgerc}');

    // Use $client just as you would \Guzzle\Http\Client
    $response = $client->get('/identity-management/v3/user-profile');
    ```

    Alternatively, you can hard code your credentials by passing the credential values to the `\Akamai\Open\EdgeGrid\Client->setAuth()` method.

    ```php
    $client = new Akamai\Open\EdgeGrid\Client([
    'base_uri' => 'https://akab-h05tnam3wl42son7nktnlnnx-kbob3i3v.luna.akamaiapis.net'
    ]);

    $client_token = 'akab-c113ntt0k3n4qtari252bfxxbsl-yvsdj';
    $client_secret = 'C113nt53KR3TN6N90yVuAgICxIRwsObLi0E67/N8eRN=';
    $access_token = 'akab-acc35t0k3nodujqunph3w7hzp7-gtm6ij';

    $client->setAuth($client_token, $client_secret, $access_token);

    // use $client just as you would \Guzzle\Http\Client
    $response = $client->get('/identity-management/v3/user-profile');
    ```

## Use

The `Akamai\Open\EdgeGrid\Client` extends `\GuzzleHttp\Client` as a drop-in replacement. It works transparently to sign API requests without changing other ways you use Guzzle.

Include the autoloader to import all the required classes.

Provide your credentials section header and appropriate endpoint information.

```php
<?php
require "vendor/autoload.php";

$client = \Akamai\Open\EdgeGrid\Client::createFromEdgeRcFile('default');

// use $client just as you would \Guzzle\Http\Client
$response = $client->get('/identity-management/v3/user-profile');

echo $response->getBody();
```

### Query string parameters

You can pass the query parameters in the url after a question mark ("?") at the end of the main URL path.

```php
<?php
require "vendor/autoload.php";

$client = \Akamai\Open\EdgeGrid\Client::createFromEdgeRcFile('default');

$headers = [
'Accept' => 'application/json',
];

$response = $client->get('/identity-management/v3/user-profile?authGrants=true&notifications=true&actions=true', $headers);

echo $response->getBody();
```

You can also pass the query parameters using a `query` request option. See the [Guzzle documentation](https://docs.guzzlephp.org/en/stable/quickstart.html#query-string-parameters) for details.

### Headers

You can enter request headers as a PSR-7 request object.

> **Note:** You don't need to include the `Content-Type` and `Content-Length` headers. The authentication layer adds these values.

```php
<?php
require "vendor/autoload.php";

$client = \Akamai\Open\EdgeGrid\Client::createFromEdgeRcFile('default');

$headers = [
  'Accept' => 'application/json'
];

$response = $client->get('/identity-management/v3/user-profile', $headers);

echo $response->getBody();
```

See the [Guzzle documentation](https://docs.guzzlephp.org/en/stable/request-options.html#headers) for details on defining headers as a `headers` request option.

### Body data

Use the [Guzzle syntax](https://docs.guzzlephp.org/en/stable/request-options.html#json) to pass simple JSON data as a `json` option in the request.

```php
<?php
require "vendor/autoload.php";

$client = \Akamai\Open\EdgeGrid\Client::createFromEdgeRcFile('default');

$headers = [
    'Content-Type' => 'application/json',
    'Accept' => 'application/json'
  ];

$body = [
  'json' => [
    'contractType' => 'Billing',
    'country' => 'USA',
    'firstName' => 'John',
    'lastName' => 'Smith',
    'phone' => '3456788765',
    'preferredLanguage' => 'English',
    'sessionTimeOut' => '30',
    'timeZone' => 'GMT'
  ]
];

$response = $client->put('/identity-management/v3/user-profile/basic-info', $body, $headers);

echo $response->getBody();
```

## Command line interface

This library provides a command line interface (CLI) with a limited set of capabilities that mimic [httpie](http://httpie.org).

### Install

Install the CLI with a composer `vendor/bin/http` or execute the PHAR file.

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

> **Note:** Our CLI shows all HTTP and body data. JSON is formatted.

|Argument|Description|
|---|---|
|`--auth-type={edgegrid,basic,digest}`|Set the authentication type. The default is `none`.|
|`--auth user:` or `--a user:`|Set the `.edgerc` section to use. Unlike `httpie-edgegrid`, the colon (`:`) is optional.|
|`Header-Name:value`|Headers and values are colon (`:`) separated.|
|`jsonKey=value`|Sends `{"jsonKey": "value"}` in the `POST` or `PUT` body. This will also automatically set the `Content-Type` and `Accept` headers to `application/json`.|
|`jsonKey:=[1,2,3]`|Allows you to specify raw JSON data, sending `{"jsonKey": [1, 2, 3]}` in the body.|

### Limitations

* You can't send `multipart/mime` (file upload) data.
* Client certs aren't supported.
* Server certs can't be verified.
* Output can't be customized.
* Responses aren't syntax highlighted.

## Guzzle middleware

This package provides three different middleware handlers you can add transparently when using the `Client`, to a standard `\GuzzleHttp\Client` or as a handler.

* The `\Akamai\Open\EdgeGrid\Handler\Authentication` for transparent API request signing.
* The `\Akamai\Open\EdgeGrid\Handler\Verbose` for output (or log) responses.
* The `\Akamai\Open\EdgeGrid\Handler\Debug` for output (or log) errors.

### Transparent use

<table>
    <thead>
        <tr>
            <th>Handler</th>
            <th>Call</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><code>Authentication</code></td>
            <td><code>Client->setAuthentication()</code> or pass in an instance of <code>\Akamai\EdgeGrid\Authentication</code> to <code>Client->__construct()</code>.</td>
        </tr>
        <tr>
            <td><code>Verbose</code></td>
            <td><code>Client->setInstanceVerbose()</code> or <code>Client::setVerbose()</code> passing in on of <code>true|resource|[resource output, resource error]</code>. The default is <code>[STDOUT, STDERR]</code>.</td>
        </tr>
        <tr>
            <td><code>Debug</code></td>
            <td><code>Client->setInstanceDebug()</code>, <code>Client::setDebug()</code>, or set the <code>debug</code> config option with <code>true|resource</code>. The default is <code>STDERR</code>.</td>
        </tr>
    </tbody>
</table>

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
    // Or hard code your credentials
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
    $handlerStack->push(new \Akamai\Open\EdgeGrid\Handler\Verbose());
    // Add the handler to a regular \GuzzleHttp\Client
    $guzzle = new \GuzzleHttp\Client([
        "handler" => $handlerStack
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
    $handlerStack->push(new \Akamai\Open\EdgeGrid\Handler\Debug());
    // Add the handler to a regular \GuzzleHttp\Client
    $guzzle = new \GuzzleHttp\Client([
        "handler" => $handlerStack
    ]);
</pre>
            </td>
        </tr>
    </tbody>
</table>

## License

Copyright © 2025 Akamai Technologies, Inc. All rights reserved

Licensed under the Apache License, Version 2.0 (the "License");
you may not use these files except in compliance with the License.
You may obtain a copy of the License at <http://www.apache.org/licenses/LICENSE-2.0>.

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
