edgegrid-php
=============
[Akamai {OPEN} EdgeGrid Authentication] for PHP

[Akamai {OPEN} EdgeGrid Authentication]: https://developer.akamai.com/introduction/Client_Auth.html

This library implements the Akamai {OPEN} EdgeGrid Authentication scheme

For more information visit the [Akamai {OPEN} Developer Community](https://developer.akamai.com).

Installation
------------

This library requires PHP 5.4 or later.  To easily install we recommend using [Composer](https://getcomposer.org/)

1. Install [Composer](https://getcomposer.org/)

    ``` sh
    $ curl -sS https://getcomposer.org/installer | php
    ```

2. Create a composer.json

    ```json
    {
        "require": {
            "akamai/edgegrid": ">=1.0.0"
        }
    }
    ```

3. Run Composer

    ```sh
    php composer.phar install
    ```

Usage
-----

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

$client = new \Akamai\EdgeGrid(
    'akab-xxxxxxxxxxxxxxxx-yyyyyyyyyyyyyyyy', // Client Token
    'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=',  // Client Secret
    'akab-xxxxxxxxxxxxxxxx-yyyyyyyyyyyyyyyy.luna.akamaiapis.net', // Hostname in Base URL
    'akab-xxxxxxxxxxxxxxxx-yyyyyyyyyyyyyyyy' // Access Token
);

$response = $client->get('/billing-usage/v1/reportSources', []);
echo $response->body;
```

Author
------

Hideki Okamoto <hokamoto@akamai.com>

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