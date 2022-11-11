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

use Caldera\Http\ServerRequest;
use Caldera\Http\Stream;
use Caldera\Http\UploadedFile;
use Caldera\Http\Uri;

class ServerRequestTest extends TestCase {

	public function testCreateServerRequest() {
		# Create request
		$request = new ServerRequest('GET', 'https://192.168.1.128:8080/api/echo?mode=default', ['Content-Type' => 'application/json'], '{}', '1.1', ['script' => __FILE__]);
		$this->assertIsArray($request->getServerParams());
		$this->assertArrayHasKey('script', $request->getServerParams());
		$this->assertIsArray($request->getCookieParams());
		$this->assertNull($request->getParsedBody());
	}

	public function testParsedBody() {
		# Create request
		$request = new ServerRequest('GET', 'https://192.168.1.128:8080/api/echo?mode=default', ['Content-Type' => 'application/x-www-form-urlencoded'], '', '1.1', ['script' => __FILE__]);
		$request = $request->withParsedBody(['foo' => 'bar', 'bar' => 'baz']);
		$this->assertIsArray($request->getParsedBody());
		$this->assertArrayHasKey('foo', $request->getParsedBody());
		try {
			$request = $request->withParsedBody(true);
			$this->fail('This must throw an InvalidArgumentException');
		} catch (Exception $e) {
			$this->assertInstanceOf(InvalidArgumentException::class, $e);
		}
	}

	public function testCookieParams() {
		# Create request
		$request = new ServerRequest('GET', 'https://192.168.1.128:8080/api/echo?mode=default', ['Content-Type' => 'application/x-www-form-urlencoded'], '', '1.1', ['script' => __FILE__]);
		$request = $request->withCookieParams(['foo' => 'bar', 'bar' => 'baz']);
		$this->assertIsArray($request->getCookieParams());
		$this->assertArrayHasKey('foo', $request->getCookieParams());
	}

	public function testQueryParams() {
		# Create request
		$request = new ServerRequest('GET', 'https://192.168.1.128:8080/api/echo?mode=default', ['Content-Type' => 'application/x-www-form-urlencoded'], '', '1.1', ['script' => __FILE__]);
		$request = $request->withQueryParams(['foo' => 'bar', 'bar' => 'baz']);
		$this->assertIsArray($request->getQueryParams());
		$this->assertArrayHasKey('foo', $request->getQueryParams());
	}

	public function testUploadedFiles() {
		# Create stream
		$stream = new Stream('{"foo":"bar"}');
		# Create UploadedFile
		$file = new UploadedFile($stream, $stream->getSize(), 0);
		# Create request
		$request = new ServerRequest('GET', 'https://192.168.1.128:8080/api/echo?mode=default', ['Content-Type' => 'application/x-www-form-urlencoded'], '', '1.1', ['script' => __FILE__]);
		$request = $request->withUploadedFiles(['file' => $file]);
		$this->assertIsArray($request->getUploadedFiles());
		$this->assertArrayHasKey('file', $request->getUploadedFiles());
	}

	public function testAttributes() {
		# Create request
		$request = new ServerRequest('GET', 'https://192.168.1.128:8080/api/echo?mode=default', ['Content-Type' => 'application/x-www-form-urlencoded'], '', '1.1', ['script' => __FILE__]);
		$request = $request->withAttribute('foo', 'bar');
		$request = $request->withAttribute('bar', 'baz');
		$request = $request->withoutAttribute('bar');
		$request = $request->withoutAttribute('qux');
		$this->assertIsArray( $request->getAttributes() );
		$this->assertEquals('bar', $request->getAttribute('foo'));
		$this->assertArrayNotHasKey('bar', $request->getAttributes());
		$this->assertEquals('baz', $request->getAttribute('bar', 'baz'));
	}
}
