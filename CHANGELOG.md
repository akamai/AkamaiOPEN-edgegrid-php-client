0.4.1
---
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
* Add support for PSR-7 requests (e.g. `->send()` and `->sendAsync()`)
* Add CLI interface to PHAR release file ([docs](https://github.com/akamai-open/AkamaiOPEN-edgegrid-php#command-line-interface))
* Move away from using `\Exception` to more appropriate, and package specific exceptions
* Use a custom User-Agent
* Fix issue with string query args
* Show request body when using the verbose handler

0.3.0
---
* Move to using GuzzleHttp Middleware
* Adds Authentication, Verbose, and Debug middleware handlers

0.2.1
---
* Add PSR-3 Logging (defaults to monolog/monolog)
* Added `\Akamai\Open\EdgeGrid\Authentication::createFromEdgeRcFile()`
* Bug fixes

0.2.0
---
* Refactor Authentication Signer out of the client for easier re-use

0.1.0
---
* Initial release
