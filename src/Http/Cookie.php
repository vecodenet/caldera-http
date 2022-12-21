<?php

declare(strict_types = 1);

/**
 * Caldera HTTP
 * HTTP message interface, handlers and factories implementation, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Http;

use RuntimeException;
use Stringable;

use Caldera\Http\CookieJar;

class Cookie implements Stringable {

	/**
	 * Cookie name
	 * @var string
	 */
	private $name;

	/**
	 * Cookie value
	 * @var string
	 */
	private $value;

	/**
	 * Cookie expiration
	 * @var int
	 */
	private $expiration;

	/**
	 * Cookie max. age
	 * @var int
	 */
	private $max_age;

	/**
	 * Cookie path
	 * @var string
	 */
	private $path;

	/**
	 * Cookie domain
	 * @var string
	 */
	private $domain;

	/**
	 * HTTP only flag
	 * @var bool
	 */
	private $http_only;

	/**
	 * Secure-only flag
	 * @var bool
	 */
	private $secure_only;

	/**
	 * Same-site policy
	 * @var string
	 */
	private $same_site;

	const SAME_SITE_NONE   = 'None';
	const SAME_SITE_LAX    = 'Lax';
	const SAME_SITE_STRICT = 'Strict';

	/**
	 * Constructor
	 * @param string $name [description]
	 */
	public function __construct(string $name) {
		$this->name = $name;
		$this->value = '';
		$this->expiration = 0;
		$this->max_age = 0;
		$this->path = '/';
		$this->domain = '';
		$this->http_only = true;
		$this->secure_only = false;
		$this->same_site = self::SAME_SITE_LAX;
	}

	/**
	 * Set cookie value
	 * @param  mixed $value Cookie value
	 * @return Cookie
	 */
	public function withValue($value) {
		$new = clone $this;
		$new->value = $value;
		return $new;
	}

	/**
	 * Set cookie expiration
	 * @param  mixed $expiration Cookie expiration
	 * @return Cookie
	 */
	public function withExpiration($expiration) {
		if ( is_string($expiration) ) {
			$expiration = strtotime($expiration);
		}
		$new = clone $this;
		$new->expiration = $expiration;
		return $new;
	}

	/**
	 * Set cookie max. age
	 * @param  mixed $max_age Cookie max. age
	 * @return Cookie
	 */
	public function withMaxAge($max_age) {
		if ( is_string($max_age) ) {
			$max_age = strtotime($max_age) - time();
		}
		$new = clone $this;
		$new->max_age = $max_age;
		return $new;
	}

	/**
	 * Set cookie path
	 * @param  string $path Cookie path
	 * @return Cookie
	 */
	public function withPath(string $path) {
		$new = clone $this;
		$new->path = $path;
		return $new;
	}

	/**
	 * Set cookie domain
	 * @param  string $domain Cookie domain
	 * @return Cookie
	 */
	public function withDomain(string $domain) {
		$new = clone $this;
		$new->domain = $domain;
		return $new;
	}

	/**
	 * Set cookie same-site policy
	 * @param  string $same_site Cookie same-site policy
	 * @return Cookie
	 */
	public function withSameSitePolicy(string $same_site) {
		$new = clone $this;
		$new->same_site = $same_site;
		return $new;
	}

	/**
	 * Set cookie HTTP-only flag
	 * @param  bool $http_only Cookie HTTP-only flag
	 * @return Cookie
	 */
	public function withHttpOnly(bool $http_only) {
		$new = clone $this;
		$new->http_only = $http_only;
		return $new;
	}

	/**
	 * Set cookie secure-only flag
	 * @param  bool $secure_only Cookie secure-only flag
	 * @return Cookie
	 */
	public function withSecureOnly(bool $secure_only) {
		$new = clone $this;
		$new->secure_only = $secure_only;
		return $new;
	}

	/**
	 * Get cookie value
	 * @return mixed
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * Get cookie expiration
	 * @return int
	 */
	public function getExpiration(): int {
		return $this->expiration;
	}

	/**
	 * Get cookie max. age
	 * @return int
	 */
	public function getMaxAge(): int {
		return $this->max_age;
	}

	/**
	 * Get cookie path
	 * @return string
	 */
	public function getPath(): string {
		return $this->path;
	}

	/**
	 * Get cookie domain
	 * @return string
	 */
	public function getDomain(): string {
		return $this->domain;
	}

	/**
	 * Get cookie same-site policy
	 * @return string
	 */
	public function getSameSitePolicy(): string {
		return $this->same_site;
	}

	/**
	 * Get cookie HTTP-only flag
	 * @return bool
	 */
	public function isHttpOnly(): bool {
		return $this->http_only;
	}

	/**
	 * Get cookie secure-only flag
	 * @return bool
	 */
	public function isSecureOnly(): bool {
		return $this->secure_only;
	}

	/**
	 * Build cookie
	 * @return string
	 */
	public function build(): string {
		$parts = [];
		$parts[] = sprintf('%s=%s', $this->name, rawurlencode($this->value));
		$same_site = $this->same_site ?: 'SameSite=None';
		if ($this->expiration && !$this->max_age) {
			$parts[] = sprintf('Expires=%s', gmdate('D, d M Y H:i:s T', $this->expiration));
		}
		if ($this->max_age) {
			$parts[] = sprintf('MaxAge=%s', $this->max_age);
		}
		if ($this->domain) {
			$parts[] = sprintf('Domain=%s', $this->domain);
		}
		if ($this->path) {
			$parts[] = sprintf('Path=%s', $this->path);
		}
		if ($this->secure_only) {
			$parts[] = 'Secure';
		}
		if ($this->http_only) {
			$parts[] = 'HttpOnly';
		}
		if ($this->same_site) {
			$parts[] = sprintf('SameSite=%s', $same_site);
			if ($same_site === self::SAME_SITE_NONE && !$this->secure_only) {
				throw new RuntimeException("When the 'SameSite' attribute is set to 'None', the 'Secure' attribute should be set as well");
			}
		}
		return implode('; ', $parts);
	}

	/**
	 * Add cookie header
	 * @return bool
	 */
	public function add(): bool {
		$cookies = new CookieJar();
		$cookie = $this->build();
		return $cookies->set($cookie);
	}

	/**
	 * Remove cookie header
	 * @return bool
	 */
	public function remove(): bool {
		$copy = clone $this;
		$copy = $copy->withValue('');
		$copy = $copy->withExpiration(1);
		return $copy->add();
	}

	/**
	 * Convert to string
	 * @return string
	 */
	public function __toString(): string {
		return $this->build();
	}
}
