<?php

declare(strict_types = 1);

/**
 * Caldera HTTP
 * HTTP message interface, handlers and factories implementation, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Http;

use Caldera\Http\Cookie;

class CookieJar {

	/**
	 * Check if cookie is set
	 * @param  string  $name Cookie name
	 * @return bool
	 */
	public function has(string $name): bool {
		return isset( $_COOKIE[$name] );
	}

	/**
	 * Get cookie value
	 * @param  string $name    Cookie name
	 * @param  mixed  $default Default value
	 * @return mixed
	 */
	public function get(string $name, $default = '') {
		return $_COOKIE[$name] ?? $default;
	}

	/**
	 * Set cookie
	 * @param mixed $cookie Cookie data
	 */
	public function set($cookie): bool {
		if (! headers_sent() ) {
			if ( $cookie instanceof Cookie ) {
				$cookie = (string) $cookie;
			}
			$header = sprintf('Set-Cookie: %s', $cookie);
			header($header, false);
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Delete cookie
	 * @param  string $name   Cookie name
	 * @param  string $domain Cookie domain
	 * @param  string $path   Cookie path
	 * @return bool
	 */
	public function delete(string $name, string $domain = '', string $path = ''): bool {
		$cookie = new Cookie($name);
		$cookie = $cookie->withValue('');
		$cookie = $cookie->withExpiration(1);
		$cookie = $cookie->withDomain($domain);
		$cookie = $cookie->withPath($path);
		$cookie = $cookie->withHttpOnly(false);
		$ret = self::set( (string) $cookie );
		$_COOKIE[$name] = '';
		unset( $_COOKIE[$name] );
		return $ret;
	}
}
