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
use RuntimeException;

use PHPUnit\Framework\TestCase;

use Caldera\Http\Factory;
use Caldera\Http\Request;
use Caldera\Http\Response;
use Caldera\Http\ServerRequest;
use Caldera\Http\Stream;
use Caldera\Http\UploadedFile;
use Caldera\Http\Uri;

class FactoryTest extends TestCase {

	/**
	 * Factory instance
	 * @var Factory
	 */
	protected static $factory;

	public static function setUpBeforeClass(): void {
		self::$factory = new Factory();
	}

	public function testCreateRequest() {
		# Create request
		$request = self::$factory->createRequest('GET', 'https://192.168.1.128:8080/api/echo?mode=default');
		$this->assertInstanceOf(Request::class, $request);
	}

	public function testCreateResponse() {
		# Create response
		$response = self::$factory->createResponse();
		$this->assertInstanceOf(Response::class, $response);
	}

	public function testCreateServerRequest() {
		# Create server request
		$request = self::$factory->createServerRequest('GET', 'https://192.168.1.128:8080/api/echo?mode=default');
		$this->assertInstanceOf(ServerRequest::class, $request);
	}

	public function testCreateServerRequestFromGlobals() {
		$_GET = [
			'foo' => 'bar',
			'bar' => 'baz',
		];
		$_POST = [
			'name' => 'foo',
			'email' => 'baz@example.org',
		];
		$_SERVER = [
			'REQUEST_URI' => '/blog/article.php?id=10&user=foo',
			'SERVER_PORT' => '443',
			'SERVER_ADDR' => '217.112.82.20',
			'SERVER_NAME' => 'www.example.org',
			'SERVER_PROTOCOL' => 'HTTP/1.1',
			'REQUEST_METHOD' => 'POST',
			'QUERY_STRING' => 'id=10&user=foo',
			'DOCUMENT_ROOT' => '/path/to/your/server/root/',
			'CONTENT_TYPE' => 'text/plain',
			'HTTP_HOST' => 'www.example.org',
			'HTTP_ACCEPT' => 'text/html',
			'HTTP_REFERRER' => 'https://example.com',
			'HTTP_USER_AGENT' => 'My User Agent',
			'HTTPS' => 'on',
			'REMOTE_ADDR' => '193.60.168.69',
			'REMOTE_PORT' => '5390',
			'SCRIPT_NAME' => '/blog/article.php',
			'SCRIPT_FILENAME' => '/path/to/your/server/root/blog/article.php',
			'PHP_SELF' => '/blog/article.php',
		];
		$_COOKIES = [
			'usr' => '1'
		];
		$_FILES = [
			'file1' => [
				'name' => 'MyFile.txt',
				'type' => 'text/plain',
				'tmp_name' => '/tmp/php/php1h4j1o',
				'error' => UPLOAD_ERR_OK,
				'size' => 123,
			],
			'file2' => [
				'name' => [
					'MyFile.txt',
					'MyFile.txt',
				],
				'type' => [
					'text/plain',
					'text/plain',
				],
				'tmp_name' => [
					'/tmp/php/php1h4j1o',
					'/tmp/php/php1h4j1o',
				],
				'error' => [
					UPLOAD_ERR_OK,
					UPLOAD_ERR_OK,
				],
				'size' => [
					123,
					123,
				],
			],
			'file3' => new UploadedFile('/tmp/php/php1h4j1o', 123, UPLOAD_ERR_OK, 'MyFile.txt', 'text/plain'),
			'file4' => [
				new UploadedFile('/tmp/php/php1h4j1o', 123, UPLOAD_ERR_OK, 'MyFile.txt', 'text/plain'),
				new UploadedFile('/tmp/php/php1h4j1o', 123, UPLOAD_ERR_OK, 'MyFile.txt', 'text/plain'),
			]
		];
		# Expected values
		$expected_headers = [
			'Host' => ['www.example.org'],
			'Content-Type' => ['text/plain'],
			'Accept' => ['text/html'],
			'Referrer' => ['https://example.com'],
			'User-Agent' => ['My User Agent'],
		];
		$expected_files = [
			'file1' => new UploadedFile('/tmp/php/php1h4j1o', 123, UPLOAD_ERR_OK, 'MyFile.txt', 'text/plain'),
			'file2' => [
				new UploadedFile('/tmp/php/php1h4j1o', 123, UPLOAD_ERR_OK, 'MyFile.txt', 'text/plain'),
				new UploadedFile('/tmp/php/php1h4j1o', 123, UPLOAD_ERR_OK, 'MyFile.txt', 'text/plain'),
			],
			'file3' => new UploadedFile('/tmp/php/php1h4j1o', 123, UPLOAD_ERR_OK, 'MyFile.txt', 'text/plain'),
			'file4' => [
				new UploadedFile('/tmp/php/php1h4j1o', 123, UPLOAD_ERR_OK, 'MyFile.txt', 'text/plain'),
				new UploadedFile('/tmp/php/php1h4j1o', 123, UPLOAD_ERR_OK, 'MyFile.txt', 'text/plain'),
			],
		];
		$expected_uri = new Uri('https://www.example.org/blog/article.php?id=10&user=foo');
		# Create server request
		$request = self::$factory->createServerRequestFromGlobals();
		$this->assertInstanceOf(ServerRequest::class, $request);
		$this->assertSame('POST', $request->getMethod());
		$this->assertEquals($expected_headers, $request->getHeaders());
		$this->assertSame('', (string) $request->getBody());
		$this->assertSame('1.1', $request->getProtocolVersion());
		$this->assertSame($_COOKIE, $request->getCookieParams());
		$this->assertSame($_POST, $request->getParsedBody());
		$this->assertSame($_GET, $request->getQueryParams());
		$this->assertEquals($expected_uri, $request->getUri());
		$this->assertEquals($expected_files, $request->getUploadedFiles());
		# With invalid $_FILES
		$_FILES = [
			'foo' => 'bar'
		];
		$this->expectException(InvalidArgumentException::class);
		$request = self::$factory->createServerRequestFromGlobals();
	}

	public function dataCreateServerRequestFromGlobalsUri(): iterable {
		$server = [
			'REQUEST_URI' => '/blog/article.php?id=10&user=foo',
			'SERVER_PORT' => '443',
			'SERVER_ADDR' => '217.112.82.20',
			'SERVER_NAME' => 'www.example.org',
			'SERVER_PROTOCOL' => 'HTTP/1.1',
			'REQUEST_METHOD' => 'POST',
			'QUERY_STRING' => 'id=10&user=foo',
			'DOCUMENT_ROOT' => '/path/to/your/server/root/',
			'HTTP_HOST' => 'www.example.org',
			'HTTPS' => 'on',
			'REMOTE_ADDR' => '193.60.168.69',
			'REMOTE_PORT' => '5390',
			'SCRIPT_NAME' => '/blog/article.php',
			'SCRIPT_FILENAME' => '/path/to/your/server/root/blog/article.php',
			'PHP_SELF' => '/blog/article.php',
		];
		return [
			'HTTPS request' => [
				'https://www.example.org/blog/article.php?id=10&user=foo',
				$server,
			],
			'HTTPS request with different on value' => [
				'https://www.example.org/blog/article.php?id=10&user=foo',
				array_merge($server, ['HTTPS' => '1']),
			],
			'HTTP request' => [
				'http://www.example.org/blog/article.php?id=10&user=foo',
				array_merge($server, ['HTTPS' => 'off', 'SERVER_PORT' => '80']),
			],
			'HTTP_HOST missing -> fallback to SERVER_NAME' => [
				'https://www.example.org/blog/article.php?id=10&user=foo',
				array_merge($server, ['HTTP_HOST' => null]),
			],
			'HTTP_HOST and SERVER_NAME missing -> fallback to SERVER_ADDR' => [
				'https://217.112.82.20/blog/article.php?id=10&user=foo',
				array_merge($server, ['HTTP_HOST' => null, 'SERVER_NAME' => null]),
			],
			'Query string with ?' => [
				'https://www.example.org/path?continue=https://example.com/path?param=1',
				array_merge($server, ['REQUEST_URI' => '/path?continue=https://example.com/path?param=1', 'QUERY_STRING' => '']),
			],
			'No query String' => [
				'https://www.example.org/blog/article.php',
				array_merge($server, ['REQUEST_URI' => '/blog/article.php', 'QUERY_STRING' => '']),
			],
			'Host header with port' => [
				'https://www.example.org:8324/blog/article.php?id=10&user=foo',
				array_merge($server, ['HTTP_HOST' => 'www.example.org:8324']),
			],
			'IPv6 local loopback address' => [
				'https://[::1]:8000/blog/article.php?id=10&user=foo',
				array_merge($server, ['HTTP_HOST' => '[::1]:8000']),
			],
			'Different port with SERVER_PORT' => [
				'https://www.example.org:8324/blog/article.php?id=10&user=foo',
				array_merge($server, ['SERVER_PORT' => '8324']),
			],
			'REQUEST_URI missing query string' => [
				'https://www.example.org/blog/article.php?id=10&user=foo',
				array_merge($server, ['REQUEST_URI' => '/blog/article.php']),
			],
			'Empty server variable' => [
				'http://localhost',
				[],
			],
		];
	}

	/**
	 * @dataProvider dataCreateServerRequestFromGlobalsUri
	 */
	public function testCreateServerRequestFromGlobalsUri($expected, $serverParams): void
	{
		$_SERVER = $serverParams;
		$_FILES = [];
		$request = self::$factory->createServerRequestFromGlobals();
		self::assertEquals(new Uri($expected), $request->getUri());
	}

	public function testCreateServerRequestFromGlobalsAuthHeaders() {
		# Try with REDIRECT_HTTP_AUTHORIZATION
		$_SERVER = [
			'REDIRECT_HTTP_AUTHORIZATION' => 'Basic Rm9vOkJhcg==',
		];
		# Create server request
		$request = self::$factory->createServerRequestFromGlobals();
		$this->assertArrayHasKey('Authorization', $request->getHeaders());
		# Try with PHP_AUTH_DIGEST
		$_SERVER = [
			'PHP_AUTH_DIGEST' => 'Basic Rm9vOkJhcg==',
		];
		# Create server request
		$request = self::$factory->createServerRequestFromGlobals();
		$this->assertArrayHasKey('Authorization', $request->getHeaders());
		# Try with PHP_AUTH_USER + PHP_AUTH_PW
		$_SERVER = [
			'PHP_AUTH_USER' => 'Foo',
			'PHP_AUTH_PW' => 'Bar',
		];
		# Create server request
		$request = self::$factory->createServerRequestFromGlobals();
		$this->assertArrayHasKey('Authorization', $request->getHeaders());
	}

	public function testCreateStream() {
		# Create stream
		$stream = self::$factory->createStream('{"foo": "bar"}');
		$this->assertInstanceOf(Stream::class, $stream);
		$stream->close();
	}

	public function testCreateStreamFromFile() {
		# Create stream
		$stream = self::$factory->createStreamFromFile(__FILE__, 'r');
		$this->assertInstanceOf(Stream::class, $stream);
		$stream->close();
		# Try wth invalid file
		try {
			$stream = self::$factory->createStreamFromFile(__DIR__, 'w');
			$this->fail('This must throw a RuntimeException');
		} catch (Exception $e) {
			$this->assertInstanceOf(RuntimeException::class, $e);
		}
		# Try wth empty filename
		try {
			$stream = self::$factory->createStreamFromFile('', 'r');
			$this->fail('This must throw a RuntimeException');
		} catch (Exception $e) {
			$this->assertInstanceOf(RuntimeException::class, $e);
		}
		# Try wth invalid mode
		try {
			$stream = self::$factory->createStreamFromFile(__FILE__, 'q');
			$this->fail('This must throw an InvalidArgumentException');
		} catch (Exception $e) {
			$this->assertInstanceOf(InvalidArgumentException::class, $e);
		}
	}

	public function testCreateStreamFromResource() {
		# Create stream
		$resource = fopen(__FILE__, 'r');
		$stream = self::$factory->createStreamFromResource($resource);
		$this->assertInstanceOf(Stream::class, $stream);
		$stream->close();
	}

	public function testCreateUploadedFile() {
		# Create file
		$stream = self::$factory->createStream('{"foo": "bar"}');
		$file = self::$factory->createUploadedFile($stream);
		$this->assertInstanceOf(UploadedFile::class, $file);
	}

	public function testCreateUri() {
		# Create uri
		$uri = self::$factory->createUri('https://192.168.1.128:8080/api/echo?mode=default');
		$this->assertInstanceOf(Uri::class, $uri);
	}
}
