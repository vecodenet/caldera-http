<?php

declare(strict_types = 1);

/**
 * Caldera HTTP
 * HTTP message interface, handlers and factories implementation, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Tests\Http;

use Exception;
use InvalidArgumentException;

use PHPUnit\Framework\TestCase;

use Caldera\Http\Uri;

class UriTest extends TestCase {

	public function testCreateUriFromString() {
		# Create uri
		$uri = new Uri('https://www.google.com/search?q=php&hl=en#gsr');
		$this->assertEquals( 'https', $uri->getScheme() );
		$this->assertEquals( 'www.google.com', $uri->getHost() );
		$this->assertEquals( '', $uri->getPort() );
		$this->assertEquals( '/search', $uri->getPath() );
		$this->assertEquals( 'q=php&hl=en', $uri->getQuery() );
		$this->assertEquals( 'gsr', $uri->getFragment() );
	}

	public function testCreateUriFromStringAuthPort() {
		# Create uri
		$uri = new Uri('http://foo:bar@192.168.1.128:1111');
		$this->assertEquals( 'http', $uri->getScheme() );
		$this->assertEquals( 'foo:bar', $uri->getUserInfo() );
		$this->assertEquals( '192.168.1.128', $uri->getHost() );
		$this->assertEquals( 1111, $uri->getPort() );
		$this->assertEquals( 'foo:bar@192.168.1.128:1111', $uri->getAuthority() );
	}

	public function testBuildUriAndCheckParts() {
		# Create uri
		$uri = new Uri();
		$uri = $uri->withScheme('http');
		$uri = $uri->withUserInfo('foo', 'bar');
		$uri = $uri->withHost('example.com');
		$uri = $uri->withPort(8080);
		$uri = $uri->withPath('/api/echo');
		$uri = $uri->withQuery('mode=default');
		$uri = $uri->withFragment('refresh');
		$this->assertEquals( 'http', $uri->getScheme() );
		$this->assertEquals( 'foo:bar', $uri->getUserInfo() );
		$this->assertEquals( 'example.com', $uri->getHost() );
		$this->assertEquals( 8080, $uri->getPort() );
		$this->assertEquals( '/api/echo', $uri->getPath() );
		$this->assertEquals( 'mode=default', $uri->getQuery() );
		$this->assertEquals( 'http://foo:bar@example.com:8080/api/echo?mode=default#refresh', (string) $uri );
	}

	public function testBuildUriWrongArguments() {
		# Create uri
		$uri = new Uri();
		try {
			$uri = $uri->withScheme(256);
		} catch (Exception $e) {
			$this->assertInstanceOf(InvalidArgumentException::class, $e);
		}
		try {
			$uri = $uri->withPath(1024);
		} catch (Exception $e) {
			$this->assertInstanceOf(InvalidArgumentException::class, $e);
		}
		try {
			$uri = $uri->withQuery(768);
		} catch (Exception $e) {
			$this->assertInstanceOf(InvalidArgumentException::class, $e);
		}
	}

	public function testBuildUriSameParameters() {
		# Create uri
		$uri = new Uri('http://foo:bar@example.com:8080/api/echo?mode=default#refresh');
		# Call withScheme with same scheme
		$old = $uri;
		$uri = $uri->withScheme('http');
		$this->assertSame($old, $uri);
		# Call withUserInfo with same user info
		$old = $uri;
		$uri = $uri->withUserInfo('foo', 'bar');
		$this->assertSame($old, $uri);
		# Call withHost with same host
		$old = $uri;
		$uri = $uri->withHost('example.com');
		$this->assertSame($old, $uri);
		# Call withPort with same port
		$old = $uri;
		$uri = $uri->withPort(8080);
		$this->assertSame($old, $uri);
		# Call withPath with same path
		$old = $uri;
		$uri = $uri->withPath('/api/echo');
		$this->assertSame($old, $uri);
		# Call withQuery with same query
		$old = $uri;
		$uri = $uri->withQuery('mode=default');
		$this->assertSame($old, $uri);
		# Call withFragment with same fragment
		$old = $uri;
		$uri = $uri->withFragment('refresh');
		$this->assertSame($old, $uri);
	}
}
