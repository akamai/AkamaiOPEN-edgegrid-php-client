# Release notes

## 2.1.1 (25 Mar, 2025)

* Upgraded `symfony/process` to 6.4.15.
* Fixed deprecations for PHP 8.4 ([PR#54](https://github.com/akamai/AkamaiOPEN-edgegrid-php-client/pull/54)).

## 2.1.0 (25 Sep, 2023)

* Changed from the extending Guzzle Client to using a trait.
* Resolved deprecation warnings.
* Upgraded to psr/log 3.0 and monolog/monolog 3.3.
* Upgraded to humbug/box 4.3.8.
* Removed a return value from the `\Akamai\Open\EdgeGrid\Client` `setLogger` function.

## 2.0.0 (18 Oct, 2022)

* Upgraded to PHP 8.1.
* Upgraded to Guzzle 7.5 and Monolog 2.0.
* Upgraded to akamai-open/edgegrid-auth 2.0.0.

## 1.0.0 (01 Sep, 2017)

* Updated to the latest dependencies.

## 1.0.0beta1 (13 Jan, 2017)

* Updated to akamai-open/edgegrid-auth 1.0.0beta1.
* Bumped minimum guzzlehttp/guzzle to 6.1.1.
* Improved continuous integration, including Windows testing.

## 0.6.4 (26 Dec, 2016)

* Bumped akamai-open-edgegrid-auth requirement to 0.6.2 (@siwinski).

## 0.6.3 (24 Dec, 2016)

* Updated to akamai-open/edgegrid-auth 0.6.2.
* Added support for using environment variables for credentials.
* General cleanup.

## 0.6.2 (17 Dec, 2016)

* Updated to akamai-open/edgegrid-auth 0.6.1 (PHP 7.1 compatibility).

## 0.6.1 (04 Nov, 2016)

* Installed bin/http using composer.
* Cleaned up tools and composer setup.
* Shrank PHAR from 5.6MB to 370KB.
* Added support for the `-A` short flag for `--auth-type` on CLI to match HTTPie.
* Updated dependencies.

## 0.6.0 (08 Oct, 2016)

* Split `\Akamai\Open\EdgeGrid\Authentication` into its own (5.3+ compatible) package.
* Moved documentation to `apigen/apigen`.
* Updated dependencies.

## 0.5.0 (12 Sep, 2016)

* Added additional getters to `\Akamai\Open\EdgeGrid\Authentication`.

## 0.4.6 (30 Aug, 2016)

* Updated dependencies.

## 0.4.5 (05 May, 2016)

* Added changes to allow running the signer on PHP 5.3+.

## 0.4.4 (29 Mar, 2016)

* Used the STDERR stream instead of the cli-only constant (Fixes #24).

## 0.4.3 (03 Dec, 2015)

* Changed the default timeout to 300 seconds to match the SLA (Fixes #20).

## 0.4.2 (29 Oct, 2015)

* Removed the shebang from the PHAR build, as it is an output when including it.

## 0.4.1 (29 Oct, 2015)

* Added more httpie-compatible features:
  * Support for `--follow` (redirects are no longer followed by default).
  * Support for `--json|-j` (default: on).
  * Support for sending data as the form data using `--form|-f`.
  * Support for `STDIN` input.
  * Support for using file contents as values for JSON and Form inputs (but not sending files themselves) using `=@` and `:=@`.
  * `URL` and `METHOD` can now be anywhere in the argument string.
  * Support for `example.org[/path]` type URLs.
  * Added `--version`.
  * Better handling of no-argument invocation.

## 0.4.0 (24 Oct, 2015)

* Added support for PSR-7 requests (e.g. `->send()` and `->sendAsync()`).
* Added the  CLI interface to the PHAR release file ([docs](https://github.com/akamai-open/AkamaiOPEN-edgegrid-php#command-line-interface)).
* Moved away from using `\Exception` to a more appropriate and package-specific exceptions.
* Added a custom User-Agent.
* Fixed an issue with string query arguments.
* Enabled showing a request body when using the verbose handler.

## 0.3.0 (21 Jul, 2015)

* Moved to using `GuzzleHttp\Middleware`.
* Added `Authentication`, `Verbose`, and `Debug` middleware handlers.

## 0.2.1 (16 Jul, 2015)

* Added PSR-3 Logging (defaults to monolog/monolog).
* Added the `\Akamai\Open\EdgeGrid\Authentication::createFromEdgeRcFile()` method.
* Fixed bugs.

## 0.2.0 (16 Jul, 2015)

* Refactored the Authentication Signer out of the client for easier re-use.

## 0.1.0 (13 Jul, 2015)

* Initial release.
