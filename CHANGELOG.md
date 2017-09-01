1.0.0
---
[01 Sep, 2017]
* Update to latest dependencies

1.0.0beta1
---
[13 Jan, 2017]
* Update to akamai-open/edgegrid-auth 1.0.0beta1
* Bump minimum guzzlehttp/guzzle to 6.1.1
* Improved continuous integration, including Windows testing

0.6.4
---
[26 Dec, 2016]
* Bump akamai-open-edgegrid-auth requirement to 0.6.2 (@siwinski)

0.6.3
---
[24 Dec, 2016]
* Update to akamai-open/edgegrid-auth 0.6.2
* Add support for using environment variables for credentials
* General cleanup

0.6.2
---
[17 Dec, 2016]
* Update to akamai-open/edgegrid-auth 0.6.1 (PHP 7.1 compatibility)

0.6.1
---
[04 Nov, 2016]
* Install bin/http using composer
* Cleanup tools and composer setup
* Shrink PHAR from 5.6MB to 370KB
* Add support for `-A` short flag for `--auth-type` on CLI to match httpie
* Update dependencies

0.6.0
---
[08 Oct, 2016]
* Split `\Akamai\Open\EdgeGrid\Authentication` into it's own (5.3+ compatible) package
* Move documentation to `apigen/apigen`
* Update dependencies

0.5.0
---
[12 Sep, 2016]
* Add additional getters to `\Akamai\Open\EdgeGrid\Authentication`:

0.4.6
---
[30 Aug, 2016]
* Update dependencies

0.4.5
---
[05 May, 2016]
* This release has some minor changes to allow running the signer on PHP 5.3+.

0.4.4
---
[29 Mar, 2016]
* Use STDERR stream instead of cli-only constant (Fixes #24)

0.4.3
---
[03 Dec, 2015]
* Changes the default timeout to 300 seconds to match the SLA (Fixes #20)

0.4.2
---
[29 Oct, 2015]
* Removed the shebang from the PHAR build as it is output when including it.

0.4.1
---
[29 Oct, 2015]
* Add more httpie-compatible features
  * Support for `--follow` (redirects are no longer followed by default)
  * Support `--json|-j` (default: on)
  * Support sending data as form data using `--form|-f`
  * Support for `STDIN` input
  * Support for using file contents as values for JSON and Form inputs (but not sending files themselves) using `=@` and `:=@`
  * `URL` and `METHOD` can now be anywhere in the argument string
  * Now supports `example.org[/path]` type URLs
  * Add `--version`
  * Better handling of no-argument invocation

0.4.0
---
[24 Oct, 2015]
* Add support for PSR-7 requests (e.g. `->send()` and `->sendAsync()`)
* Add CLI interface to PHAR release file ([docs](https://github.com/akamai-open/AkamaiOPEN-edgegrid-php#command-line-interface))
* Move away from using `\Exception` to more appropriate, and package specific exceptions
* Use a custom User-Agent
* Fix issue with string query args
* Show request body when using the verbose handler

0.3.0
---
[21 Jul, 2015]
* Move to using GuzzleHttp Middleware
* Adds Authentication, Verbose, and Debug middleware handlers

0.2.1
---
[16 Jul, 2015]
* Add PSR-3 Logging (defaults to monolog/monolog)
* Added `\Akamai\Open\EdgeGrid\Authentication::createFromEdgeRcFile()`
* Bug fixes

0.2.0
---
[16 Jul, 2015]
* Refactor Authentication Signer out of the client for easier re-use

0.1.0
---
[13 Jul, 2015]

* Initial release
