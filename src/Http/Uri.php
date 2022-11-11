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

use Psr\Http\Message\UriInterface;

class Uri implements UriInterface {

	/**
	 * Default ports
	 */
	private const DEFAULT_PORTS = [
		'http'  => 80,
		'https' => 443,
		'ftp' => 21,
		'gopher' => 70,
		'nntp' => 119,
		'news' => 119,
		'telnet' => 23,
		'tn3270' => 23,
		'imap' => 143,
		'pop' => 110,
		'ldap' => 389,
	];

	private const DEFAULT_HOST = 'localhost';

	/**
	 * Uri scheme
	 * @var string
	 */
	protected $scheme = '';

	/**
	 * Uri host
	 * @var string
	 */
	protected $host = '';

	/**
	 * Uri port
	 * @var mixed
	 */
	protected $port;

	/**
	 * Uri user info
	 * @var string
	 */
	protected $user_info = '';

	/**
	 * Uri path
	 * @var string
	 */
	protected $path = '';

	/**
	 * Uri query string
	 * @var string
	 */
	protected $query = '';

	/**
	 * Uri fragment
	 * @var string
	 */
	protected $fragment = '';

	/**
	 * Constructor
	 * @param string $uri URI to parse and wrap.
	 */
	public function __construct(string $uri = '') {
		$this->fromString($uri);
	}

	/**
	 * Return the string representation as a URI reference
	 * @return string
	 */
	public function __toString() {
		return $this->toString();
	}

	/**
	 * Retrieve the scheme component of the URI
	 * @return string
	 */
	public function getScheme() {
		return $this->scheme;
	}

	/**
	 * Retrieve the authority component of the URI
	 * @return string
	 */
	public function getAuthority() {
		if ($this->host === '') {
			return '';
		}
		$authority = $this->host;
		if ($this->user_info !== '') {
			$authority = $this->user_info . '@' . $authority;
		}
		if ($this->port !== null) {
			$authority .= ':' . $this->port;
		}
		return $authority;
	}

	/**
	 * Retrieve the user information component of the URI
	 * @return string
	 */
	public function getUserInfo() {
		return $this->user_info;
	}

	/**
	 * Retrieve the host component of the URI
	 * @return string
	 */
	public function getHost() {
		return $this->host;
	}

	/**
	 * Retrieve the port component of the URI
	 * @return mixed
	 */
	public function getPort() {
		return $this->port;
	}

	/**
	 * Retrieve the path component of the URI
	 * @return string The URI path
	 */
	public function getPath() {
		return $this->path;
	}

	/**
	 * Retrieve the query string of the URI
	 * @return string
	 */
	public function getQuery() {
		return $this->query;
	}

	/**
	 * Retrieve the fragment component of the URI
	 * @return string
	 */
	public function getFragment() {
		return $this->fragment;
	}

	/**
	 * Return an instance with the specified scheme
	 * @param  string $scheme The scheme to use with the new instance
	 * @return Uri
	 */
	public function withScheme($scheme) {
		if (! is_string($scheme) ) {
			throw new InvalidArgumentException('Invalid scheme');
		}
		$scheme = strtolower($scheme);
		if ($this->scheme === $scheme) {
			return $this;
		}
		$new = clone $this;
		$new->scheme = $scheme;
		if ($new->host === '' && ($new->scheme === 'http' || $new->scheme === 'https')) {
			$new->host = self::DEFAULT_HOST;
		}
		return $new;
	}

	/**
	 * Return an instance with the specified user information
	 * @param  string $user     The user name to use for authority
	 * @param  mixed  $password The password associated with $user
	 * @return Uri
	 */
	public function withUserInfo($user, $password = null) {
		$info = $user;
		if (null !== $password && '' !== $password) {
			$info .= ':' . $password;
		}
		if ($this->user_info === $info) {
			return $this;
		}
		$new = clone $this;
		$new->user_info = $info;
		return $new;
	}

	/**
	 * Return an instance with the specified host
	 * @param  string $host The hostname to use with the new instance
	 * @return Uri
	 */
	public function withHost($host) {
		if ($this->host === $host) {
			return $this;
		}
		$host = strtolower($host);
		$new = clone $this;
		$new->host = $host;
		if ($new->host === '' && ($new->scheme === 'http' || $new->scheme === 'https')) {
			$new->host = self::DEFAULT_HOST;
		}
		return $new;
	}

	/**
	 * Return an instance with the specified port
	 * @param  mixed $port The port to use with the new instance
	 * @return Uri A new instance with the specified port
	 */
	public function withPort($port) {
		if ($this->port === $port) {
			return $this;
		}
		$new = clone $this;
		$new->port = (int) $port;
		if ( $new->isDefaultPort() ) {
			$new->port = null;
		}
		return $new;
	}

	/**
	 * Return an instance with the specified path
	 * @param  string $path The path to use with the new instance
	 * @return Uri
	 */
	public function withPath($path) {
		if (! is_string($path) ) {
			throw new InvalidArgumentException('Invalid path');
		}
		if ($this->path === $path) {
			return $this;
		}
		$new = clone $this;
		$new->path = $path;
		return $new;
	}

	/**
	 * Return an instance with the specified query string
	 * @param  string $query The query string to use with the new instance
	 * @return Uri
	 */
	public function withQuery($query) {
		if (! is_string($query) ) {
			throw new InvalidArgumentException('Invalid query');
		}
		if ($this->query === $query) {
			return $this;
		}
		$new = clone $this;
		$new->query = $query;
		return $new;
	}

	/**
	 * Return an instance with the specified URI fragment
	 * @param  string $fragment The fragment to use with the new instance
	 * @return Uri
	 */
	public function withFragment($fragment) {
		if ($this->fragment === $fragment) {
			return $this;
		}
		$new = clone $this;
		$new->fragment = $fragment;
		return $new;
	}

	/**
	 * Whether the URI has the default port of the current scheme
	 * @return boolean
	 */
	protected function isDefaultPort(): bool {
		$default = self::DEFAULT_PORTS[ $this->getScheme() ] ?? null;
		return $this->getPort() === null || ($this->getPort() === $default);
	}

	/**
	 * Parse from string
	 * @param  string $uri String to parse
	 * @return void
	 */
	protected function fromString(string $uri) {
		if ($uri) {
			$parts = parse_url($uri);
			if ($parts === false) {
				throw new InvalidArgumentException('Invalid URI');
			}
			$this->scheme = isset( $parts['scheme'] ) ? $parts['scheme'] : '';
			$this->host = isset( $parts['host'] ) ? $parts['host'] : '';
			$this->port = isset( $parts['port'] ) ? (int) $parts['port'] : null;
			$this->user_info = isset( $parts['user'] ) ? $parts['user'] : '';
			$this->path = isset( $parts['path'] ) ? $parts['path'] : '';
			$this->query = isset( $parts['query'] ) ? $parts['query'] : '';
			$this->fragment = isset( $parts['fragment'] ) ? $parts['fragment'] : '';
			if ( isset($parts['pass']) ) {
				$this->user_info .= ':' . $parts['pass'];
			}
			if ( $this->isDefaultPort() ) {
				$this->port = null;
			}
			if ($this->host === '' && ($this->scheme === 'http' || $this->scheme === 'https')) {
				$this->host = self::DEFAULT_HOST;
			}
		}
	}

	/**
	 * Convert to string
	 * @return string
	 */
	protected function toString(): string {
		$scheme = $this->getScheme();
		$authority = $this->getAuthority();
		$path = $this->getPath();
		$query = $this->getQuery();
		$fragment = $this->getFragment();
		#
		$uri = '';
		if ('' !== $scheme) {
			$uri .= $scheme . ':';
		}
		if ('' !== $authority) {
			$uri .= '//' . $authority;
		}
		if ('' !== $path) {
			if ('/' !== $path[0]) {
				if ('' !== $authority) {
					$path = '/' . $path;
				}
			} elseif (isset($path[1]) && '/' === $path[1]) {
				if ('' === $authority) {
					$path = '/' . \ltrim($path, '/');
				}
			}
			$uri .= $path;
		}
		if ('' !== $query) {
			$uri .= '?' . $query;
		}
		if ('' !== $fragment) {
			$uri .= '#' . $fragment;
		}
		return $uri;
	}
}