<?php

declare(strict_types = 1);

/**
 * Caldera HTTP
 * HTTP message interface, handlers and factories implementation, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Http {

	function header($header) {
		\Caldera\Tests\Http\CookieTest::$header = $header;
	}

	function headers_sent() {
		return \Caldera\Tests\Http\CookieTest::$headers_sent;
	}
}

namespace Caldera\Tests\Http {

	use RuntimeException;

	use PHPUnit\Framework\TestCase;

	use Caldera\Http\Cookie;
	use Caldera\Http\CookieJar;

	class CookieTest extends TestCase {

		public static $header;

		public static $headers_sent;

		public function testCreateCookie() {
			# Create cookie
			$cookie = new Cookie('Test');
			$cookie = $cookie->withValue('4d79b35927147f5ee175b436db8c8a5b');
			$cookie = $cookie->withExpiration(1924444800); // Dec, 25 2030, 16:00:00 GMT
			$cookie = $cookie->withPath('/');
			$cookie = $cookie->withHttpOnly(true);
			$cookie = $cookie->withSecureOnly(true);
			$cookie = $cookie->withDomain('localhost');
			$this->assertEquals('Test=4d79b35927147f5ee175b436db8c8a5b; Expires=Wed, 25 Dec 2030 16:00:00 GMT; Domain=localhost; Path=/; Secure; HttpOnly; SameSite=Lax', (string) $cookie);
			$this->assertEquals('4d79b35927147f5ee175b436db8c8a5b', $cookie->getValue());
			$this->assertEquals(1924444800, $cookie->getExpiration());
			$this->assertEquals(0, $cookie->getMaxAge());
			$this->assertEquals('/', $cookie->getPath());
			$this->assertEquals('localhost', $cookie->getDomain());
			$this->assertEquals('Lax', $cookie->getSameSitePolicy());
			$this->assertTrue( $cookie->isHttpOnly() );
			$this->assertTrue( $cookie->isSecureOnly() );
			#
			$cookie->add();
			$this->assertEquals('Set-Cookie: Test=4d79b35927147f5ee175b436db8c8a5b; Expires=Wed, 25 Dec 2030 16:00:00 GMT; Domain=localhost; Path=/; Secure; HttpOnly; SameSite=Lax', static::$header);
			#
			$cookie->remove();
			$this->assertEquals('Set-Cookie: Test=; Expires=Thu, 01 Jan 1970 00:00:01 GMT; Domain=localhost; Path=/; Secure; HttpOnly; SameSite=Lax', static::$header);
		}

		public function testCreateWithMaxAge() {
			# Create cookie
			$cookie = new Cookie('Test');
			$cookie = $cookie->withValue('4d79b35927147f5ee175b436db8c8a5b');
			$cookie = $cookie->withExpiration('+2 weeks');
			$cookie = $cookie->withMaxAge('+1 hour');
			$this->assertEquals('Test=4d79b35927147f5ee175b436db8c8a5b; MaxAge=3600; Path=/; HttpOnly; SameSite=Lax', (string) $cookie);
		}

		public function testCreateWithSameSiteNone() {
			# Create cookie
			$cookie = new Cookie('Test');
			$this->expectException(RuntimeException::class);
			$cookie = $cookie->withSameSitePolicy(Cookie::SAME_SITE_NONE);
			$cookie->build();
		}

		public function testCookieJar() {
			global $_COOKIE;
			$cookie_jar = new CookieJar();
			$_COOKIE['Test'] = '4d79b35927147f5ee175b436db8c8a5b';
			$this->assertTrue( $cookie_jar->has('Test') );
			$this->assertEquals('4d79b35927147f5ee175b436db8c8a5b', $cookie_jar->get('Test') );
			$ret = $cookie_jar->set('Test=4d79b35927147f5ee175b436db8c8a5b; Expires=Wed, 25 Dec 2030 16:00:00 GMT; Domain=localhost; Path=/; Secure; HttpOnly; SameSite=Lax');
			$this->assertTrue($ret);
			$this->assertEquals('Set-Cookie: Test=4d79b35927147f5ee175b436db8c8a5b; Expires=Wed, 25 Dec 2030 16:00:00 GMT; Domain=localhost; Path=/; Secure; HttpOnly; SameSite=Lax', static::$header);
			$cookie_jar->delete('Test');
			$this->assertEquals('Set-Cookie: Test=; Expires=Thu, 01 Jan 1970 00:00:01 GMT; SameSite=Lax', static::$header);
			static::$headers_sent = true;
			$ret = $cookie_jar->set('Test=4d79b35927147f5ee175b436db8c8a5b; Expires=Wed, 25 Dec 2030 16:00:00 GMT; Domain=localhost; Path=/; Secure; HttpOnly; SameSite=Lax');
			$this->assertFalse($ret);
			static::$headers_sent = false;
			$cookie = new Cookie('Foo');
			$cookie = $cookie->withValue('4d79b35927147f5ee175b436db8c8a5b');
			$cookie_jar->set($cookie);
			$this->assertEquals('Set-Cookie: Foo=4d79b35927147f5ee175b436db8c8a5b; Path=/; HttpOnly; SameSite=Lax', static::$header);
		}
	}
}
