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

use Caldera\Http\Request;
use Caldera\Http\Stream;
use Caldera\Http\Uri;

class RequestTest extends TestCase {

	public function testCreateRequest() {
		# Create request
		$request = new Request('GET', 'https://192.168.1.128:8080/api/echo?mode=default', ['Content-Type' => 'application/json'], '{}');
		$this->assertEquals( 'GET', $request->getMethod() );
		$this->assertEquals( '{}', $request->getBody() );
		$this->assertEquals( '/api/echo?mode=default', $request->getRequestTarget() );
		$request = $request->withProtocolVersion('2.0');
		$this->assertEquals( 'application/json', $request->getHeaderLine('Content-Type') );
		$this->assertTrue( $request->hasHeader('Content-Type') );
		$this->assertIsArray( $request->getHeader('Content-Type') );
		$this->assertIsArray( $request->getHeaders() );
		$this->assertEquals( '2.0', $request->getProtocolVersion() );
	}

	public function testBuildRequest() {
		# Create Uri
		$uri = new Uri('https://192.168.1.128:8080/api/echo?mode=default');
		# Create stream
		$body = new Stream('{}');
		# Create request
		$request = new Request('POST', 'localhost');
		# Set method and Uri
		$request = $request->withMethod('GET');
		$request = $request->withUri($uri);
		$this->assertEquals( 'GET', $request->getMethod() );
		$this->assertEquals( '/api/echo?mode=default', $request->getRequestTarget() );
		# Set body and headers
		$request = $request->withBody($body);
		$request = $request->withHeader('Content-Type', 'application/json');
		$request = $request->withAddedHeader('Content-Type', 'utf-8');
		$request = $request->withHeader('Content-Length', 1024);
		$request = $request->withHeader('X-FooBar', ['Foo', 'Bar']);
		$request = $request->withoutHeader('Content-Length');
		$request = $request->withoutHeader('X-Sender');
		$this->assertEquals( '{}', $request->getBody() );
		$this->assertEquals( 'application/json, utf-8', $request->getHeaderLine('Content-Type') );
		$this->assertEquals( 'Foo, Bar', $request->getHeaderLine('X-FooBar') );
		$this->assertFalse( $request->hasHeader('Content-Length') );
		$this->assertTrue( $request->hasHeader('Content-Type') );
		$this->assertIsArray( $request->getHeader('Content-Type') );
		$this->assertIsArray( $request->getHeaders() );
	}

	public function testFromUri() {
		# Create request
		$uri = new Uri('https://192.168.1.128:8080');
		$request = new Request('POST', $uri);
		$this->assertEquals( 'https://192.168.1.128:8080', $request->getUri() );
	}

	public function testEmptyPathAppendSlash() {
		# Create request
		$request = new Request('POST', 'https://192.168.1.128:8080');
		$this->assertEquals( '/', $request->getRequestTarget() );
	}

	public function testWithRequestTarget() {
		# Create request
		$request = new Request('POST', 'https://192.168.1.128:8080');
		$request = $request->withRequestTarget('/api/echo');
		$this->assertEquals( '/api/echo', $request->getRequestTarget() );
	}

	public function testWithInvalidRequestTarget() {
		# Create request
		$request = new Request('POST', 'https://192.168.1.128:8080');
		try {
			$request = $request->withRequestTarget('No whitespace allowed');
			$this->fail('This must throw an InvalidArgumentException');
		} catch (Exception $e) {
			$this->assertInstanceOf(InvalidArgumentException::class, $e);
		}
	}

	public function testWithMethod() {
		# Create request
		$request = new Request('POST', 'https://192.168.1.128:8080');
		$request = $request->withMethod('PUT');
		$this->assertEquals( 'PUT', $request->getMethod() );
	}

	public function testWithInvalidMethod() {
		# Create request
		$request = new Request('POST', 'https://192.168.1.128:8080');
		try {
			$request = $request->withMethod(200);
			$this->fail('This must throw an InvalidArgumentException');
		} catch (Exception $e) {
			$this->assertInstanceOf(InvalidArgumentException::class, $e);
		}
	}

	public function testReplaceHeader() {
		# Create request
		$request = new Request('POST', 'https://192.168.1.128:8080/api/echo?mode=default');
		$request = $request->withHeader('Content-Type', 'application/json');
		$request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');
		$this->assertEquals( 'application/x-www-form-urlencoded', $request->getHeaderLine('Content-Type') );
	}

	public function testInvalidHeaders() {
		# Create request
		$request = new Request('POST', 'https://192.168.1.128:8080/api/echo?mode=default');
		# Try to add header with invalid value
		try {
			$request = $request->withHeader('Content-Type', (object) ['foo' => 'bar']);
			$this->fail('This must throw an InvalidArgumentException');
		} catch (Exception $e) {
			$this->assertInstanceOf(InvalidArgumentException::class, $e);
		}
		try {
			$request = $request->withHeader('Content-Type', []);
			$this->fail('This must throw an InvalidArgumentException');
		} catch (Exception $e) {
			$this->assertInstanceOf(InvalidArgumentException::class, $e);
		}
		# Now with valid value but invalid name
		try {
			$request = $request->withHeader(1024, 'text/plain');
			$this->fail('This must throw an InvalidArgumentException');
		} catch (Exception $e) {
			$this->assertInstanceOf(InvalidArgumentException::class, $e);
		}
		try {
			$request = $request->withHeader('áéíóúñ', 'text/plain');
			$this->fail('This must throw an InvalidArgumentException');
		} catch (Exception $e) {
			$this->assertInstanceOf(InvalidArgumentException::class, $e);
		}
		# Now try to append a value to an invalid header
		try {
			$request = $request->withAddedHeader(1024, 'text/plain');
			$this->fail('This must throw an InvalidArgumentException');
		} catch (Exception $e) {
			$this->assertInstanceOf(InvalidArgumentException::class, $e);
		}
	}

	public function testUnknownHeader() {
		# Create request
		$request = new Request('POST', 'https://192.168.1.128:8080/api/echo?mode=default');
		$header = $request->getHeader('Content-Lenght');
		$this->assertEquals( [], $request->getHeader('Content-Lenght') );
	}

	public function testGetEmptyBody() {
		# Create request
		$request = new Request('POST', 'https://192.168.1.128:8080/api/echo?mode=default');
		$body = $request->getBody();
		$this->assertInstanceOf(Stream::class, $body);
	}
}
