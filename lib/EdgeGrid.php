<?php

/**
 *
 * Author: Michael Coury <mcoury@vorien.com>
 * Based on work by: Hideki Okamoto <hokamoto@akamai.com>
 *
 * For more information visit https://developer.akamai.com
 *
 * Copyright 2014 Akamai Technologies, Inc. All rights reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Akamai;

/**
 * Client to access {OPEN} API
 *
 * @package Akamai
 * @author Michael Coury <mcoury@vorien.com>
 * @since PHP 5.4
 * @version 2.0.0
 */
class EdgeGrid {

	private $client_token;
	private $client_secret;
	private $host;
	private $access_token;
	private $headers_to_sign;
	private $max_body = 131072;
	private $body_to_sign = null;
	private $error_array;   // Maintain a record of errors
	private $crlf;  // Set to \n for CLI, <br> for HTTP
	public $path;  // Base path
	public $query = array();  // Array of query parameters
	public $headers = array();  // Array of optional headers
	public $method = 'GET';   // Request method
	public $protocol = 'https';  // Request protocol
	public $body = ""; // Request body, ignored if method !== POST
	public $timestamp; // Timestamp of request
	public $nonce;  // Unique id of request
	public $timeout = 30;   // Request timeout (s)
	public $verbose = false;

	/**
	 * Constructor
	 * 
	 * @param string $section			[default]  Section of .edgerc to use, default: 'default'
	 * @param string $edgerc_location	[optional]	Full path to .edgerc file
	 * 
	 * Loads configuration data from:
	 * 1) @param string $edgerc_location (Full path to .edgerc)
	 * 2) $HOME/.edgerc
	 * 3) _SESSION["DOCUMENT_ROOT"]
	 * 
	 * @throws		Array of errors
	 */
	function __construct($verbose = false, $section = 'default', $edgerc_location = null) {
		$this->verbose = $verbose;

		$this->method = strtoupper($this->method);
		$this->protocol = strtolower($this->protocol);

		if ($section !== 'default') {
			$this->verbose('section', $section);
		}
		if ($edgerc_location) {
			$this->verbose('edgerc_location', $edgerc_location);
		}

		if ($edgerc = $this->loadEdgerc($section, $edgerc_location)) {
			$this->verbose('Credentials', $edgerc);
			$this->host = $edgerc[$section]['host'];
			$this->client_token = $edgerc[$section]['client_token'];
			$this->client_secret = $edgerc[$section]['client_secret'];
			$this->access_token = $edgerc[$section]['access_token'];
			$this->headers_to_sign = [];
			$this->max_body = isset($edgerc[$section]['max_body']) ? $edgerc[$section]['max_body'] : 131072;
		} else {
			$this->verbose('Errors', $this->error_array);
		}
	}

	/**
	 * Add query string to path
	 * 
	 * */
	public function addQueryToPath() {
		$this->path = '/' . preg_replace('/^\/+|\/+$/', '', $this->path);
		if ($this->query) {
			$this->path .= '?' . http_build_query($this->query);
		}
	}

	/**
	 * Clean up host string
	 * 
	 * */
	public function cleanHost() {
		$this->host = preg_replace('/^\/+|\/+$/', '', $this->host);
	}

	/**
	 * Load Certification file (.edgerc)
	 * 
	 * @param string $section			[default]	Section of .edgerc to use (default: 'default')
	 * @param string $edgerc_location	[optional]	Path to .edgerc location, (no trailing /)
	 * 
	 * Loads configuration data from:
	 * 1) $edgerc_location (if supplied)
	 * 2) $HOME/.edgerc (CLI)
	 * 3) _SESSION["DOCUMENT_ROOT"] (HTTP)
	 * 
	 * @return Credentials array or false with error set
	 *
	 * 	
	 * */
	private function loadEdgerc($section = 'default', $edgerc_location = null) {
		if ($edgerc_location) {
			if (file_exists($edgerc_location . "/.edgerc")) {
				$this->crlf = "\n";
				$edgerc = $edgerc_location . "/.edgerc";
			} else {
				$this->error_array[] = $edgerc_location . " exists, " . $edgerc_location . "/.edgerc" . " not found";
				return false;
			}
		} else if (isset($_SERVER['HOME'])) {
			if (file_exists($_SERVER['HOME'] . "/.edgerc")) {
				$this->crlf = "\n";
				$edgerc = $_SERVER['HOME'] . "/.edgerc";
			} else {
				$this->error_array[] = "HOME exists, " . $_SERVER['HOME'] . "/.edgerc" . " not found";
				return false;
			}
		} else if (isset($_SERVER['DOCUMENT_ROOT'])) {
			if (file_exists($_SERVER['DOCUMENT_ROOT'] . "/.edgerc")) {
				$this->crlf = "<br>";
				$edgerc = $_SERVER['DOCUMENT_ROOT'] . "/.edgerc";
			} else {
				$this->error_array[] = "DOCUMENT_ROOT exists, " . $_SERVER['DOCUMENT_ROOT'] . "/.edgerc" . " not found";
				return false;
			}
		} else {
			$this->error_array[] = "Neither HOME nor DOCUMENT_ROOT exist";
			return false;
		}

		return $this->parseEdgerc($edgerc, $section);
	}

	/**
	 * Parse Credentials File (.edgerc)
	 * 
	 * @param string $section			[default]	Section of .edgerc to use (default: 'default')
	 * @param string $edgerc_location	[optional]	Path to .edgerc location, (no trailing /)
	 * 
	 * @return Credentials array or false with error set
	 * 
	 */
	private function parseEdgerc($edgerc, $section) {
		$parsed_array = array();
		$contents = file_get_contents($edgerc);
		preg_match_all("/\[([^\]].*?)\]\s*([^\[]*)/", $contents, $matches);
		if (false !== array_search($section, $matches[1])) {
			$sectionkey = array_search($section, $matches[1]);
			preg_match_all("/^\s*(host|client_token|client_secret|access_token|max_body)\s*[:=]\s*([^\s]*)\s*$/m", $matches[2][$sectionkey], $tokens);
			foreach ($tokens[1] as $tokenkey => $tokenvalue) {
				$parsed_array[$section][$tokenvalue] = $tokens[2][$tokenkey];
			}
		} else {
			$this->error_array[] = "Section: $section not found in $edgerc";
			return false;
		}
		return $parsed_array;
	}

	/**
	 * Makes a HTTP request
	 *
	 * 	@return
	 * 		array(
	 * 			body		Response body  (curl_exec)
	 * 			error		Request errors  (curl_error)
	 * 			header		cURL Header Info  (curl_info)
	 * 		)
	 * 
	 */
	public function request() {
		$this->verbose('protocol', $this->protocol);
		$this->verbose('method', $this->method);
		$this->verbose('host', $this->host);
		$this->verbose('path', $this->path);
		$this->verbose('query', $this->query);
		$this->verbose('headers', $this->headers);
		$this->verbose('body', $this->body);
		$this->verbose('timeout', $this->timeout);

		$this->addQueryToPath();
		$this->verbose('path + query', $this->path);
		$this->cleanHost();
		$this->verbose('cleanHost', $this->host);

		$url = $this->protocol . '://' . $this->host . $this->path;
		$this->verbose('url', $url);

		$this->headers['Authorization'] = $this->makeAuthHeader();


		foreach ($this->headers as $header_key => $header_value) {
			$header_array[] = $header_key . ":" . $header_value;
		}

		$ch = curl_init();
		$options = array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_CUSTOMREQUEST => $this->method,
			CURLOPT_POSTFIELDS => $this->body,
			CURLOPT_HEADER => false,
			CURLINFO_HEADER_OUT => true,
			CURLOPT_HTTPHEADER => $header_array,
			CURLOPT_TIMEOUT => $this->timeout
		);
		curl_setopt_array($ch, $options);

		$this->verbose('body at exec', $this->body);
		$response_body = curl_exec($ch);
		$response_error = curl_error($ch);
		$response_headers = curl_getinfo($ch);
		curl_close($ch);

		$response['error'] = $response_error;
		$response['body'] = $response_body;
		$response['header'] = $response_headers;
		return $response;
	}

	/**
	 * Returns the computed Authorization header
	 *
	 * @return string
	 */
	public function makeAuthHeader() {
		$this->timestamp = $this->timestamp ? $this->timestamp : $this->getEdgeGridTimestamp();
		$this->nonce = $this->nonce ? $this->nonce : $this->makeNonce();

		$auth_header = 'EG1-HMAC-SHA256 ' .
				'client_token=' . $this->client_token . ';' .
				'access_token=' . $this->access_token . ';' .
				'timestamp=' . $this->timestamp . ';' .
				'nonce=' . $this->nonce . ';';

		$this->verbose('auth_header', $auth_header);

		switch ($this->method) {
			case 'POST':
				$this->body_to_sign = $this->body;
				break;
			case 'PUT':
				break;
			default:
				$this->body = null;
				break;
		}
		$this->verbose('body_to_sign', $this->body_to_sign);

		$signed_auth_header = $auth_header . 'signature=' . $this->signRequest($auth_header);
		$this->verbose('signed_auth_header', $signed_auth_header);

		return $signed_auth_header;
	}

	/**
	 * Returns a signature of the given request, timestamp and auth_header
	 *
	 * @param string $auth_header
	 * @return string
	 */
	private function signRequest($auth_header) {
		$signature = $this->makeBase64HmacSha256(
				$this->makeDataToSign($auth_header), $this->makeSigningKey($this->timestamp)
		);

		$this->verbose('signature', $signature);
		return $signature;
	}

	/**
	 * Returns a string with all data that will be signed
	 *
	 * @param string $auth_header
	 * @return string
	 */
	private function makeDataToSign($auth_header) {
		$data_to_sign = array(
			$this->method,
			$this->protocol,
			$this->host,
			$this->path,
			/*
			 * MFC 12/24/2014
			 * Replaced headers with null as the servers don't accept headers as part of the signature
			 * 
			 * $this->canonicalizeHeaders($this->headers),
			 */
			null,
			$this->body_to_sign ? $this->makeContentHash($this->body_to_sign) : null,
			$auth_header
		);
		$this->verbose('data_to_sign (array)', $data_to_sign);

		$return_dts = implode("\t", $data_to_sign);
		$this->verbose('return_dts (imploded string)', str_replace("\t", "\\t", $return_dts));

		return $return_dts;
	}

	/**
	 * Returns headers in normalized form
	 *
	 * @return string
	 * 
	 * NOTE:  Not currently in use
	 */
	private function canonicalizeHeaders() {
		$canonicalized_headers = [];
		foreach ($this->headers as $key => $value) {
			$canonicalized_headers[strtolower($key)] = preg_replace('/\s+/', ' ', trim($value));
		}

		ksort($canonicalized_headers);

		$serialized_header = '';
		foreach ($canonicalized_headers as $key => $value) {
			$serialized_header .= $key . ':' . $value . "\t";
		}
		$this->verbose('headers', $this->headers);
		$this->verbose('canonicalized_headers', $canonicalized_headers);
		$this->verbose('serialized_header', str_replace("\t", "\\t", $serialized_header));

		return $serialized_header;
	}

	/**
	 * Returns a hash of the HTTP POST body
	 *
	 * @return string
	 */
	private function makeContentHash() {
		if (!$this->body) {
			return '';
		} elseif (strlen($this->body) > $this->max_body) {
			return $this->makeBase64Sha256(substr($this->body, 0, $this->max_body));
		} else {
			return $this->makeBase64Sha256($this->body);
		}
	}

	/**
	 * Creates a signing key based on the secret and timestamp
	 *
	 * @return string
	 */
	private function makeSigningKey() {
		$signing_key = $this->makeBase64HmacSha256($this->timestamp, $this->client_secret);
		$this->verbose('signing_key', $signing_key);
		return $signing_key;
	}

	/**
	 * Returns the current time in the format understood by {OPEN} API
	 *
	 * @return string Timestamp
	 */
	public static function getEdgeGridTimestamp() {
		return (new \DateTime(null, new \DateTimeZone('UTC')))->format('Ymd\TH:i:sO');
	}

	/**
	 * Returns a new nonce (unique identifier)
	 *
	 * @return string 16 bytes unique identifier
	 */
	public static function makeNonce() {
		return bin2hex(openssl_random_pseudo_bytes(16));
	}

	/**
	 * Returns Base64 encoded HMAC-SHA256 Hash
	 *
	 * @param string $data
	 * @param string $key
	 * @return string
	 */
	public static function makeBase64HmacSha256($data, $key) {
		return base64_encode(hash_hmac('sha256', $data, $key, true));
	}

	/**
	 * Returns Base64 encoded SHA256 Hash
	 *
	 * @param string $data
	 * @return string
	 */
	private static function makeBase64Sha256($data) {
		return base64_encode(hash('sha256', $data, true));
	}

	/**
	 * Dumps debug data to output location
	 * Activate by setting the $verbose construct parameter to true
	 *
	 * @param string $title
	 * @param string/array $data
	 */
	private function verbose($title, $data) {
		if ($this->verbose) {
			if ($title) {
				var_dump($title);
			}
			if ($data) {
				var_dump($data);
			}
		}
	}

}
