<?php

declare(strict_types = 1);

/**
 * Caldera HTTP
 * HTTP message interface, handlers and factories implementation, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Http;

use InvalidArgumentException;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

use Caldera\Http\Uri;
use Caldera\Http\Stream;
use Caldera\Http\Request;

class ServerRequest extends Request implements ServerRequestInterface {

	/**
	 * Attributes array
	 * @var array
	 */
	protected $attributes = [];

	/**
	 * Cookies array
	 * @var array
	 */
	protected $cookieParams = [];

	/**
	 * Parsed body
	 * @var mixed
	 */
	protected $parsedBody;

	/**
	 * Query params array
	 * @var array
	 */
	protected $queryParams = [];

	/**
	 * Server params array
	 * @var array
	 */
	protected $serverParams;

	/**
	 * Uploaded files array
	 * @var array
	 */
	protected $uploadedFiles = [];

	/**
	 * Constructor
	 * @param string $method       HTTP method
	 * @param mixed  $uri          Request URI
	 * @param array  $headers      Request headers
	 * @param mixed  $body         Request body
	 * @param string $version      HTTP protocol version
	 * @param array  $serverParams Server paremeters
	 */
	public function __construct(string $method, $uri, array $headers = [], $body = null, string $version = '1.1', array $serverParams = []) {
		$this->serverParams = $serverParams;
		if (!($uri instanceof UriInterface)) {
			$uri = new Uri($uri);
		}
		$this->method = $method;
		$this->uri = $uri;
		$this->protocol = $version;
		$this->setHeaders($headers);
		if (!$this->hasHeader('Host')) {
			$this->updateHostFromUri();
		}
		if ($body !== '' && $body !== null) {
			if ($body instanceof StreamInterface) {
				$this->stream = $body;
			} else {
				$this->stream = new Stream($body);
			}
		}
	}

	/**
	 * Retrieve server parameters.
	 * @return array
	 */
	public function getServerParams() {
		return $this->serverParams;
	}

	/**
	 * Retrieve cookies.
	 * @return array
	 */
	public function getCookieParams() {
		return $this->cookieParams;
	}

	/**
	 * Return an instance with the specified cookies
	 * @param  array $cookies Array of key/value pairs representing cookies.
	 * @return ServerRequest
	 */
	public function withCookieParams(array $cookies) {
		$new = clone $this;
		$new->cookieParams = $cookies;
		return $new;
	}

	/**
	 * Retrieve query string arguments
	 * @return array
	 */
	public function getQueryParams() {
		return $this->queryParams;
	}

	/**
	 * Return an instance with the specified query string arguments
	 * @param  array $query Array of query string arguments, typically from $_GET
	 * @return ServerRequest
	 */
	public function withQueryParams(array $query) {
		$new = clone $this;
		$new->queryParams = $query;
		return $new;
	}

	/**
	 * Retrieve normalized file upload data
	 * @return array
	 */
	public function getUploadedFiles() {
		return $this->uploadedFiles;
	}

	/**
	 * Create a new instance with the specified uploaded files
	 * @param  array $uploadedFiles An array tree of UploadedFileInterface instances
	 * @return ServerRequest
	 */
	public function withUploadedFiles(array $uploadedFiles) {
		$new = clone $this;
		$new->uploadedFiles = $uploadedFiles;
		return $new;
	}

	/**
	 * Retrieve any parameters provided in the request body
	 * @return mixed
	 */
	public function getParsedBody() {
		return $this->parsedBody;
	}

	/**
	 * Return an instance with the specified body parameters
	 * @param  mixed $data The deserialized body data. This will typically be in an array or object
	 * @return ServerRequest
	 */
	public function withParsedBody($data) {
		if ( !is_array($data) && !is_object($data) && $data !== null ) {
			throw new InvalidArgumentException('First parameter to withParsedBody MUST be object, array or null');
		}
		$new = clone $this;
		$new->parsedBody = $data;
		return $new;
	}

	/**
	 * Retrieve attributes derived from the request
	 * @return array Attributes derived from the request
	 */
	public function getAttributes() {
		return $this->attributes;
	}

	/**
	 * Retrieve a single derived request attribute
	 * @param  string $name The attribute name
	 * @param  mixed  $default Default value to return if the attribute does not exist
	 * @return mixed
	 */
	public function getAttribute($name, $default = null) {
		if (! array_key_exists($name, $this->attributes) ) {
			return $default;
		}
		return $this->attributes[$name];
	}

	/**
	 * Return an instance with the specified derived request attribute
	 * @param  string $name The attribute name
	 * @param  mixed $value The value of the attribute
	 * @return ServerRequest
	 */
	public function withAttribute($name, $value) {
		$new = clone $this;
		$new->attributes[$name] = $value;
		return $new;
	}

	/**
	 * Return an instance that removes the specified derived request attribute
	 * @param  string $name The attribute name
	 * @return ServerRequest
	 */
	public function withoutAttribute($name) {
		if (! array_key_exists($name, $this->attributes) ) {
			return $this;
		}
		$new = clone $this;
		unset( $new->attributes[$name] );
		return $new;
	}
}