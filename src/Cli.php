<?php

/**
 * Akamai {OPEN} EdgeGrid Auth Client
 *
 * @author Davey Shafik <dshafik@akamai.com>
 * @copyright Copyright 2016 Akamai Technologies, Inc. All rights reserved.
 * @license Apache 2.0
 * @link https://github.com/akamai-open/AkamaiOPEN-edgegrid-php-client
 * @link https://developer.akamai.com
 * @link https://developer.akamai.com/introduction/Client_Auth.html
 */

namespace Akamai\Open\EdgeGrid;

/**
 * Class Cli
 * @package Akamai\Open\EdgeGrid\Client
 */
class Cli
{
    /**
     * @var \League\CLImate\CLImate
     */
    protected $climate;

    /**
     * Cli constructor.
     */
    public function __construct()
    {
        $this->climate = new \League\CLImate\CLImate();
    }

    /**
     * Execute the CLI
     */
    public function run()
    {
        if ($this->parseArguments()) {
            $this->executeCommand();
        }
    }

    /**
     * Parse incoming arguments
     *
     * @return bool|void
     */
    protected function parseArguments()
    {
        $args = $this->getNamedArgs();

        $this->climate->arguments->add($args);

        if ($_SERVER['argc'] === 1) {
            $this->help();
            return false;
        }

        if ($this->climate->arguments->defined('help')) {
            $this->help();
            return;
        }

        if ($this->climate->arguments->defined('version')) {
            echo $this->version();
            return;
        }

        try {
            $this->climate->arguments->parse($_SERVER['argv']);

            $padding = count($args);
            foreach ($this->climate->arguments->toArray() as $arg) {
                if ($arg === null) {
                    --$padding;
                }
            }
            $argSize = count($_SERVER['argv']) - $padding - 1;
            for ($i = 0; $i < $argSize; $i++) {
                $args['arg-' . $i] = [];
            }
            $this->climate->arguments->add($args);
            $this->climate->arguments->parse($_SERVER['argv']);
        } catch (\Exception $e) {
        }

        return true;
    }

    /**
     * Execute the HTTP request
     *
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    protected function executeCommand()
    {
        static $methods = [
            'HEAD',
            'GET',
            'POST',
            'PUT',
            'DELETE'
        ];

        \Akamai\Open\EdgeGrid\Client::setDebug(true);
        \Akamai\Open\EdgeGrid\Client::setVerbose(true);

        $args = $this->climate->arguments->all();
        $client = new Client();

        if ($this->climate->arguments->defined('auth-type')) {
            $auth = $this->climate->arguments->get('auth');
            if (
                $this->climate->arguments->get('auth-type') === 'edgegrid' ||
                (!$this->climate->arguments->defined('auth-type'))
            ) {
                $section = 'default';
                if ($this->climate->arguments->defined('auth')) {
                    $section = (substr($auth, -1) === ':') ? substr($auth, 0, -1) : $auth;
                }
                $client = Client::createFromEdgeRcFile($section);
            }

            if (in_array($this->climate->arguments->get('auth-type'), ['basic', 'digest'])) {
                if (!$this->climate->arguments->defined('auth') || $this->climate->arguments->get('auth') === null) {
                    $this->help();
                    return;
                }

                $auth = [
                    $auth,
                    null,
                    $this->climate->arguments->get('auth-type')
                ];

                if (strpos(':', $auth[0]) !== false) {
                    list($auth[0], $auth[1]) = explode(':', $auth[0]);
                }

                $client = new Client(['auth' => $auth]);
            }
        }

        $method = 'GET';
        $options = [];
        $body = [];

        foreach ($args as $arg) {
            $value = $arg->value();
            if (empty($value) || is_bool($value) || $arg->longPrefix()) {
                continue;
            }

            if (in_array(strtoupper($value), $methods)) {
                $method = $arg->value();
                continue;
            }

            if (!isset($url) && preg_match('@^(http(s?)://|:).*$@', trim($value))) {
                $url = $value;

                if ($url[0] === ':') {
                    $url = substr($url, 1);
                }

                continue;
            }

            $matches = [];
            if (preg_match('/^(?<key>.*?):=(?<file>@?)(?<value>.*?)$/', $value, $matches)) {
                if (!$value = $this->getArgValue($matches)) {
                    return false;
                }

                $body[$matches['key']] = json_decode($value);
                continue;
            }

            if (
                preg_match('/^(?<header>.*?):(?<value>.*?)$/', $value, $matches)
                && !preg_match('@^http(s?)://@', $value)
            ) {
                $options['headers'][$matches['header']] = $matches['value'];
                continue;
            }

            if (preg_match('/^(?<key>.*?)=(?<file>@?)(?<value>.*?)$/', $value, $matches)) {
                if (!$value = $this->getArgValue($matches)) {
                    return false;
                }

                $body[$matches['key']] = $matches['value'];
                continue;
            }

            if (!isset($url)) {
                $url = $value;
                continue;
            }

            $this->help();
            $this->climate->error('Unknown argument: ' . $value);

            return false;
        }

        $stdin = '';
        $fp = fopen('php://stdin', 'rb');
        if ($fp) {
            stream_set_blocking($fp, false);
            $stdin = fgets($fp);
            if (!empty(trim($stdin))) {
                while (!feof($fp)) {
                    $stdin .= fgets($fp);
                }
                fclose($fp);
            }
            $stdin = rtrim($stdin);
        }

        if (!empty($stdin) && !empty($body)) {
            $this->help();
            $this->climate->error(
                'error: Request body (from stdin or a file) and request data (key=value) cannot be mixed.'
            );
            return;
        }

        if (!empty($stdin)) {
            $body = $stdin;
        }

        if (count($body) && !$this->climate->arguments->defined('form')) {
            if (!isset($options['headers']['Content-Type'])) {
                $options['headers']['Content-Type'] = 'application/json';
            }
            if (!isset($options['headers']['Accept'])) {
                $options['headers']['Accept'] = 'application/json';
            }
            $options['body'] = (!is_string($body)) ? json_encode($body) : $body;
        }

        if (count($body) && $this->climate->arguments->defined('form')) {
            if (!isset($options['headers']['Content-Type'])) {
                $options['headers']['Content-Type'] = 'application/x-www-form-urlencoded; charset=utf-8';
            }

            $options['body'] = (!is_string($body)) ? http_build_query($body, '', null, PHP_QUERY_RFC1738) : $body;
        }

        $options['allow_redirects'] = false;
        if ($this->climate->arguments->defined('follow')) {
            $options['allow_redirects'] = true;
        }

        return $client->request($method, $url, $options);
    }

    /**
     * Display CLI help
     */
    public function help()
    {
        $arguments = new \League\CLImate\Argument\Manager();
        $arguments->description('Akamai {OPEN} Edgegrid Auth for PHP Client (v' . Client::VERSION . ')');
        $arguments->add($this->getNamedArgs());
        $arguments->usage($this->climate, $_SERVER['argv']);
    }

    /**
     * Return the client version
     *
     * @return string
     */
    public function version()
    {
        return Client::VERSION;
    }

    /**
     * Handle named arguments
     *
     * @return array
     */
    protected function getNamedArgs()
    {
        $args = [
            'help' => [
                'longPrefix' => 'help',
                'prefix' => 'h',
                'description' => 'Show this help output',
                'noValue' => true
            ],
            'auth-type' => [
                'longPrefix' => 'auth-type',
                'prefix' => 'A',
                'description' => '{basic, digest, edgegrid}'
            ],
            'auth' => [
                'longPrefix' => 'auth',
                'prefix' => 'a',
                'description' => '.edgerc section name, or user[:password]'
            ],
            'json' => [
                'longPrefix' => 'json',
                'prefix' => 'j',
                'description' => '(default) Data items from the command line are serialized as a JSON object.',
                'noValue' => true
            ],
            'follow' => [
                'longPrefix' => 'follow',
                'description' => 'Set this flag if redirects are allowed',
                'noValue' => true
            ],
            'form' => [
                'longPrefix' => 'form',
                'prefix' => 'f',
                'description' => 'Data items from the command line are serialized as form fields',
                'noValue' => true
            ],
            'version' => [
                'longPrefix' => 'version',
                'description' => 'Show version',
                'noValue' => true
            ],
            'METHOD' => [
                'description' => 'HTTP Method (default: GET)'
            ],
            'URL' => [
                'required' => true,
            ]
        ];

        return $args;
    }

    /**
     * Get argument values
     *
     * @param $matches
     * @return bool|string
     */
    protected function getArgValue($matches)
    {
        $value = $matches['value'];
        if (!empty($matches['file'])) {
            if (!file_exists($matches['value']) || !is_readable($matches['value'])) {
                $this->climate->error('Unable to read input file: ' . $matches['value']);
                return false;
            }
            $value = file_get_contents($matches['value']);
        }

        return $value;
    }
}
