<?php

declare(strict_types = 1);

/**
 * Caldera HTTP
 * HTTP message interface, handlers and factories implementation, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Http;

use CURLFile;
use RuntimeException;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

use Caldera\Http\Response;
use Caldera\Http\Stream;

class Client implements ClientInterface {

	/**
	 * Certificate bundle path
	 * @var string
	 */
	protected $cainfo = '';

	/**
	 * Connection timeout
	 * @var int
	 */
	protected $timeout;

	/**
	 * Follow redirects
	 * @var bool
	 */
	protected $redirect;

	/**
	 * Cookie jar file
	 * @var string
	 */
	protected $cookie_jar;

	/**
	 * User-agent
	 * @var string
	 */
	protected $user_agent;

	/**
	 * Referer
	 * @var string
	 */
	protected $referer;

	/**
	 * Download to file
	 * @var string
	 */
	protected $download;

	/**
	 * Files array
	 * @var array
	 */
	protected $files;

	/**
	 * Constructor
	 * @param array $options Options array
	 */
	public function __construct(array $options = []) {
		$this->cainfo = $options['cainfo'] ?? '';
		$this->timeout = $options['timeout'] ?? 0;
		$this->redirect = $options['redirect'] ?? true;
		$this->cookie_jar = $options['cookie_jar'] ?? '';
		$this->user_agent = $options['user_agent'] ?? '';
		$this->referer = $options['referer'] ?? '';
		$this->download = $options['download'] ?? '';
		$this->files = $options['files'] ?? '';
		# Try to load the cainfo path from php.ini
		$this->cainfo = $this->cainfo ? $this->cainfo : (ini_get('curl.cainfo') ?: null);
		$this->cainfo = $this->cainfo ? $this->cainfo : (ini_get('openssl.cafile') ?: null);
	}

	/**
	 * Set connection timeout
	 * @param int $timeout Timeout in seconds
	 * @return $this
	 */
	public function setTimeout(int $timeout) {
		$this->timeout = $timeout;
		return $this;
	}

	/**
	 * Set follow redirects flag
	 * @param bool $redirect Follow redirects flag
	 * @return $this
	 */
	public function setRedirect(bool $redirect) {
		$this->redirect = $redirect;
		return $this;
	}

	/**
	 * Set cookie jar file
	 * @param string $cookie_jar Cookie jar file
	 * @return $this
	 */
	public function setCookieJar(string $cookie_jar) {
		$this->cookie_jar = $cookie_jar;
		return $this;
	}

	/**
	 * Set user-agent
	 * @param string $user_agent User-agent
	 * @return $this
	 */
	public function setUserAgent(string $user_agent) {
		$this->user_agent = $user_agent;
		return $this;
	}

	/**
	 * Set referer
	 * @param string $referer Referer
	 * @return $this
	 */
	public function setReferer(string $referer) {
		$this->referer = $referer;
		return $this;
	}

	/**
	 * Set certificate bundle path
	 * @param string $cainfo Certificate bundle path
	 * @return $this
	 */
	public function setCertificateBundle(string $cainfo) {
		$this->cainfo = $cainfo;
		return $this;
	}

	/**
	 * Get connection timeout
	 * @return int
	 */
	public function getTimeout(): int {
		return $this->timeout;
	}

	/**
	 * Get follow redirects flag
	 * @return bool
	 */
	public function getRedirect(): bool {
		return $this->redirect;
	}

	/**
	 * Get cookie jar file
	 * @return string
	 */
	public function getCookieJar(): string {
		return $this->cookie_jar;
	}

	/**
	 * Get user-agent
	 * @return string
	 */
	public function getUserAgent(): string {
		return $this->user_agent;
	}

	/**
	 * Get referer
	 * @return string
	 */
	public function getReferer(): string {
		return $this->referer;
	}

	/**
	 * Get certificate bundle path
	 * @return string
	 */
	public function getCertificateBundle(): string {
		return $this->cainfo;
	}

	/**
	 * Execute a GET request
	 * @param  string $uri     URI to request
	 * @param  array  $options Options array
	 * @return ResponseInterface
	 */
	public function get(string $uri, array $options = []): ResponseInterface {
		$headers = $options['headers'] ?? [];
		#
		$this->setOverrides($options);
		#
		$request = new Request('get', $uri, $headers);
		return $this->sendRequest($request);
	}

	/**
	 * Execute a POST request
	 * @param  string $uri     URI to request
	 * @param  array  $options Options array
	 * @return ResponseInterface
	 */
	public function post(string $uri, array $options = []): ResponseInterface {
		$headers = $options['headers'] ?? [];
		$fields = $options['fields'] ?? [];
		#
		$this->setOverrides($options);
		#
		if ($fields) {
			$body = http_build_query($fields);
		} else {
			$json = $options['json'] ?? '';
			$body = $options['body'] ?? '';
			if ($json) {
				$body = json_encode($json);
				$headers['Content-Type'] = 'application/json';
			}
		}
		$request = new Request('post', $uri, $headers, $body);
		return $this->sendRequest($request);
	}

	/**
	 * Execute a PUT request
	 * @param  string $uri     URI to request
	 * @param  array  $options Options array
	 * @return ResponseInterface
	 */
	public function put(string $uri, array $options = []): ResponseInterface {
		$headers = $options['headers'] ?? [];
		$json = $options['json'] ?? '';
		$body = $options['body'] ?? '';
		#
		$this->setOverrides($options);
		#
		if ($json) {
			$body = json_encode($json);
			$headers['Content-Type'] = 'application/json';
		}
		$request = new Request('put', $uri, $headers, $body);
		return $this->sendRequest($request);
	}

	/**
	 * Execute a PATCH request
	 * @param  string $uri     URI to request
	 * @param  array  $options Options array
	 * @return ResponseInterface
	 */
	public function patch(string $uri, array $options = []): ResponseInterface {
		$headers = $options['headers'] ?? [];
		$json = $options['json'] ?? '';
		$body = $options['body'] ?? '';
		#
		$this->setOverrides($options);
		#
		if ($json) {
			$body = json_encode($json);
			$headers['Content-Type'] = 'application/json';
		}
		$request = new Request('patch', $uri, $headers, $body);
		return $this->sendRequest($request);
	}

	/**
	 * Execute a DELETE request
	 * @param  string $uri     URI to request
	 * @param  array  $options Options array
	 * @return ResponseInterface
	 */
	public function delete(string $uri, array $options = []): ResponseInterface {
		$headers = $options['headers'] ?? [];
		$json = $options['json'] ?? '';
		$body = $options['body'] ?? '';
		#
		$this->setOverrides($options);
		#
		if ($json) {
			$body = json_encode($json);
			$headers['Content-Type'] = 'application/json';
		}
		$request = new Request('delete', $uri, $headers, $body);
		return $this->sendRequest($request);
	}

	/**
	 * Execute a OPTIONS request
	 * @param  string $uri     URI to request
	 * @param  array  $options Options array
	 * @return ResponseInterface
	 */
	public function options(string $uri, array $options = []): ResponseInterface {
		$headers = $options['headers'] ?? [];
		$request = new Request('options', $uri, $headers);
		#
		$this->setOverrides($options);
		#
		return $this->sendRequest($request);
	}

	/**
	 * Execute a HEAD request
	 * @param  string $uri     URI to request
	 * @param  array  $options Options array
	 * @return ResponseInterface
	 */
	public function head(string $uri, array $options = []): ResponseInterface {
		$headers = $options['headers'] ?? [];
		$request = new Request('head', $uri, $headers);
		#
		$this->setOverrides($options);
		#
		return $this->sendRequest($request);
	}

	/**
	 * Sends a PSR-7 request and returns a PSR-7 response.
	 * @param  RequestInterface $request
	 * @return ResponseInterface
	 */
	public function sendRequest(RequestInterface $request): ResponseInterface {
		$resource = null;
		$url = (string) $request->getUri();
		# Open connection
		$ch = curl_init();
		# Set the url, number of POST vars, POST data, etc
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $this->redirect);
		# Add headers
		$headers = $request->getHeaders();
		if ($headers) {
			$curl_headers = [];
			foreach ($headers as $key => $value) {
				$value = implode('; ', $value);
				$curl_headers[] = "{$key}: {$value}";
			}
			curl_setopt($ch, CURLOPT_HTTPHEADER, $curl_headers);
		}
		if ( $this->timeout ) {
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);
		}
		if ( $this->user_agent ) {
			curl_setopt($ch, CURLOPT_USERAGENT, $this->user_agent);
		}
		if ( $this->referer ) {
			curl_setopt($ch, CURLOPT_REFERER, $this->referer);
		}
		if ( $this->cookie_jar ) {
			curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_jar);
			curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_jar);
		}
		# SSL
		if ( preg_match('/https:\/\//', $url) === 1 ) {
			if ($this->cainfo && file_exists($this->cainfo)) {
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
				curl_setopt($ch, CURLOPT_CAINFO, $this->cainfo);
			} else {
				throw new RuntimeException('Invalid Certificate Authority (CA) bundle path, you need a valid copy of it to perform HTTPS requests.');
			}
		}
		# POST/PUT/DELETE
		if (strtolower( $request->getMethod() ) != 'get') {
			if ( strtolower( $request->getMethod() ) == 'post' ) {
				curl_setopt($ch, CURLOPT_POST, true);
			} else {
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($request->getMethod()));
			}
			$body = $request->getBody();
			if ( $body->getSize() > 0 && $body->isSeekable() ) {
				$body->rewind();
			}
			if ($this->files) {
				# Request has files, send them directly
				$fields = [];
				parse_str($body->getContents(), $fields);
				foreach ($this->files as $name => $file) {
					$title = pathinfo($file, PATHINFO_BASENAME);
					$mime = mime_content_type($file) ?: null;
					$fields[$name] = ($file instanceof CURLFile) ? $file : new CURLFile($file, $mime, $title);
				}
				curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
			} else {
				# Request hasn't files, just send the body
				curl_setopt($ch, CURLOPT_POSTFIELDS, $body->getContents());
			}
		}
		# File download
		if ($this->download) {
			$resource = fopen($this->download, 'w+');
			curl_setopt($ch, CURLOPT_FILE, $resource);
		}
		# Prepare response objects
		$response_body = '';
		$response_headers = [];
		$response_status = 0;
		# Headers callback
		curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($curl, $header) use (&$response_headers) {
			$len = strlen($header);
			$header = explode(':', $header, 2);
			if (count($header) < 2)
				return $len;
			$response_headers[ strtolower( trim( $header[0] ) ) ] = trim( $header[1] );
			return $len;
		});
		# Execute request
		$response_body = curl_exec($ch);
		$response_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$response = new Response($response_status);
		$response = $response->withBody( new Stream(($this->download && $resource) ? '' : $response_body) );
		if ($response_headers) {
			foreach ($response_headers as $key => $value) {
				$response = $response->withHeader($key, $value);
			}
		}
		#
		curl_close($ch);
		if ($this->download && $resource) {
			fclose($resource);
		}
		return $response;
	}

	/**
	 * Apply overrides
	 * @param array $options Options array
	 */
	protected function setOverrides(array $options): void {
		$this->cainfo     = $options['cainfo']     ?? $this->cainfo;
		$this->timeout    = $options['timeout']    ?? $this->timeout;
		$this->redirect   = $options['redirect']   ?? $this->redirect;
		$this->cookie_jar = $options['cookie_jar'] ?? $this->cookie_jar;
		$this->user_agent = $options['user_agent'] ?? $this->user_agent;
		$this->referer    = $options['referer']    ?? $this->referer;
		$this->download   = $options['download']   ?? $this->download;
		$this->files      = $options['files']      ?? $this->files;
	}
}
