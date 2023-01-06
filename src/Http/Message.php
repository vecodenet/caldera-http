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

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\MessageInterface;

use Caldera\Http\Stream;

abstract class Message implements MessageInterface {

	/**
	 * Headers array
	 * @var array
	 */
	protected $headers = [];

	/**
	 * Header names array
	 * @var array
	 */
	protected $headerNames = [];

	/**
	 * HTTP protocol version
	 * @var string
	 */
	protected $protocol = '1.1';

	/**
	 * Body stream
	 * @var StreamInterface
	 */
	protected $stream;

	/**
	 * Retrieves the HTTP protocol version as a string
	 * @return string
	 */
	public function getProtocolVersion() {
		return $this->protocol;
	}

	/**
	 * Return an instance with the specified HTTP protocol version
	 * @param  string $version HTTP protocol version
	 * @return static
	 */
	public function withProtocolVersion($version) {
		if ($this->protocol === $version) {
			return $this;
		}
		$new = clone $this;
		$new->protocol = $version;
		return $new;
	}

	/**
	 * Retrieves all message header values
	 * @return array
	 */
	public function getHeaders() {
		return $this->headers;
	}

	/**
	 * Checks if a header exists by the given case-insensitive name
	 * @param  string $name Case-insensitive header field name
	 * @return bool
	 */
	public function hasHeader($name) {
		return isset($this->headerNames[strtolower($name)]);
	}

	/**
	 * Retrieves a message header value by the given case-insensitive name
	 * @param  string $name Case-insensitive header field name
	 * @return array
	 */
	public function getHeader($name) {
		$header = strtolower($name);
		if (!isset($this->headerNames[$header])) {
			return [];
		}
		$header = $this->headerNames[$header];
		return $this->headers[$header];
	}

	/**
	 * Retrieves a comma-separated string of the values for a single header
	 * @param  string $name Case-insensitive header field name
	 * @return string
	 */
	public function getHeaderLine($name) {
		return implode(', ', $this->getHeader($name));
	}

	/**
	 * Return an instance with the provided value replacing the specified header
	 * @param  string  $name Case-insensitive header field name
	 * @param  mixed   $value Header value(s)
	 * @return static
	 */
	public function withHeader($name, $value) {
		$this->assertHeader($name);
		$value = $this->normalizeHeaderValue($value);
		$normalized = strtolower($name);
		$new = clone $this;
		if (isset($new->headerNames[$normalized])) {
			unset($new->headers[$new->headerNames[$normalized]]);
		}
		$new->headerNames[$normalized] = $name;
		$new->headers[$name] = $value;
		return $new;
	}

	/**
	 * Return an instance with the specified header appended with the given value
	 * @param  string  $name Case-insensitive header field name to add
	 * @param  mixed   $value Header value(s)
	 * @return static
	 */
	public function withAddedHeader($name, $value) {
		if ( !is_string($name) || '' === $name ) {
			throw new InvalidArgumentException('Invalid header');
		}
		$new = clone $this;
		$new->setHeaders([$name => $value]);
		return $new;
	}

	/**
	 * Return an instance without the specified header
	 * @param  string  $name Case-insensitive header field name to remove
	 * @return static
	 */
	public function withoutHeader($name) {
		$name = strtolower($name);
		if (!isset($this->headerNames[$name])) {
			return $this;
		}
		$header = $this->headerNames[$name];
		$new = clone $this;
		unset($new->headers[$header], $new->headerNames[$name]);
		return $new;
	}

	/**
	 * Gets the body of the message
	 * @return StreamInterface
	 */
	public function getBody() {
		if ($this->stream === null) {
			$this->stream = new Stream('');
		}
		return $this->stream;
	}

	/**
	 * Return an instance with the specified message body
	 * @param  StreamInterface $body Body
	 * @return static
	 */
	public function withBody(StreamInterface $body) {
		if (! $body instanceof StreamInterface ) {
			throw new InvalidArgumentException('Invalid body');
		}
		if ($this->stream === $body) {
			return $this;
		}
		$new = clone $this;
		$new->stream = $body;
		return $new;
	}

	/**
	 * Set message headers
	 * @param array $headers Headers array
	 */
	protected function setHeaders(array $headers): void {
		foreach ($headers as $header => $value) {
			# Numeric array keys are converted to int by PHP.
			$header = (string) $header;
			$this->assertHeader($header);
			$value = $this->normalizeHeaderValue($value);
			$normalized = strtolower($header);
			if ( isset( $this->headerNames[$normalized] ) ) {
				$header = $this->headerNames[$normalized];
				$this->headers[$header] = array_merge($this->headers[$header], $value);
			} else {
				$this->headerNames[$normalized] = $header;
				$this->headers[$header] = $value;
			}
		}
	}

	/**
	 * Normalize header value
	 * @param  mixed $value Header value
	 * @return array
	 */
	private function normalizeHeaderValue($value): array {
		if (!is_array($value)) {
			return $this->trimAndValidateHeaderValues([$value]);
		}
		if (count($value) === 0) {
			throw new InvalidArgumentException('Header value can not be an empty array.');
		}
		return $this->trimAndValidateHeaderValues($value);
	}

	/**
	 * Trim and validate header values
	 * @param  array  $values Array of values
	 * @return array
	 */
	private function trimAndValidateHeaderValues(array $values): array {
		return array_map(function ($value) {
			if (!is_scalar($value) && null !== $value) {
				throw new InvalidArgumentException(sprintf(
					'Header value must be scalar or null but %s provided.',
					is_object($value) ? get_class($value) : gettype($value)
				));
			}
			$trimmed = trim((string) $value, " \t");
			$this->assertValue($trimmed);
			return $trimmed;
		}, array_values($values));
	}

	/**
	 * Assert header name
	 * @param  mixed $header Header name
	 * @return void
	 */
	private function assertHeader($header): void {
		if (!is_string($header)) {
			throw new InvalidArgumentException(sprintf(
				'Header name must be a string but %s provided.',
				is_object($header) ? get_class($header) : gettype($header)
			));
		}
		if (! preg_match('/^[a-zA-Z0-9\'`#$%&*+.^_|~!-]+$/', $header)) {
			throw new InvalidArgumentException(
				sprintf('"%s" is not valid header name', $header)
			);
		}
	}

	/**
	 * Assert header value
	 * @param  string $value Header value
	 * @return void
	 */
	private function assertValue(string $value): void {
		if (! preg_match('/^[\x20\x09\x21-\x7E\x80-\xFF]*$/', $value)) {
			throw new InvalidArgumentException(sprintf('"%s" is not valid header value', $value));
		}
	}
}