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

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

use Caldera\Http\Uri;
use Caldera\Http\Stream;
use Caldera\Http\Message;

class Request extends Message implements RequestInterface {

	/**
	 * Request method
	 * @var string
	 */
	protected $method;

	/**
	 * Request target
	 * @var string
	 */
	protected $requestTarget;

	/**
	 * Request URI
	 * @var UriInterface
	 */
	protected $uri;

	/**
	 * Constructor
	 * @param string $method          HTTP method
	 * @param mixed  $uri             Request URI
	 * @param array  $headers         Request headers
	 * @param mixed  $body            Request body
	 * @param string $protocolVersion HTTP protocol version
	 */
	function __construct($method, $uri, array $headers = [], $body = null, $protocolVersion = '1.1') {
		if ( is_string($uri) ) {
			$uri = new Uri($uri);
		} elseif (! $uri instanceof UriInterface ) {
			throw new InvalidArgumentException('Invalid URI');
		}
		$this->method = strtoupper($method);
		$this->uri = $uri;
		$this->protocol = $protocolVersion;
		$this->setHeaders($headers);
		$host = $uri->getHost();
		if ($host && !$this->hasHeader('Host')) {
			$this->updateHostFromUri();
		}
		if ($body) {
			$this->stream = new Stream($body);
		}
	}

	/**
	 * Retrieves the message's request target
	 * @return string
	 */
	public function getRequestTarget() {
		if ($this->requestTarget !== null) {
			return $this->requestTarget;
		}
		$target = $this->uri->getPath();
		if ($target === '') {
			$target = '/';
		}
		if ($this->uri->getQuery() !== '') {
			$target .= '?' . $this->uri->getQuery();
		}
		return $target;
	}

	/**
	 * Return an instance with the specific request-target
	 * @param  mixed   $requestTarget
	 * @return Request
	 */
	public function withRequestTarget($requestTarget) {
		if ( preg_match('#\s#', $requestTarget) ) {
			throw new InvalidArgumentException('Invalid request target');
		}
		$new = clone $this;
		$new->requestTarget = $requestTarget;
		return $new;
	}

	/**
	 * Retrieves the HTTP method of the request
	 * @return string
	 */
	public function getMethod() {
		return $this->method;
	}

	/**
	 * Return an instance with the provided HTTP method
	 * @param  string  $method Case-sensitive method
	 * @return Request
	 */
	public function withMethod($method) {
		if (! is_string($method)  ) {
			throw new InvalidArgumentException('Invalid method');
		}
		$new = clone $this;
		$new->method = strtoupper($method);
		return $new;
	}

	/**
	 * Retrieves the URI instance
	 * @return UriInterface
	 */
	public function getUri() {
		return $this->uri;
	}

	/**
	 * Returns an instance with the provided URI
	 * @param  UriInterface $uri          New request URI to use
	 * @param  bool         $preserveHost Preserve the original state of the Host header
	 * @return Request
	 */
	public function withUri(UriInterface $uri, $preserveHost = false) {
		if ($uri === $this->uri) {
			return $this;
		}
		$new = clone $this;
		$new->uri = $uri;
		if (!$preserveHost || !$this->hasHeader('Host')) {
			$new->updateHostFromUri();
		}
		return $new;
	}

	/**
	 * Set the host header from the URI
	 */
	protected function updateHostFromUri(): void {
		$host = $this->uri->getHost();
		$port = $this->uri->getPort();
		if ($host === '') {
			return;
		}
		if ($port) {
			$host .= ':' . $port;
		}
		$this->headers = ['Host' => [$host]] + $this->headers;
		$this->headerNames = ['host' => [$host]] + $this->headerNames;
	}
}