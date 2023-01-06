<?php

declare(strict_types = 1);

/**
 * Caldera HTTP
 * HTTP message interface, handlers and factories implementation, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Tests\Http;

use RuntimeException;

use PHPUnit\Framework\TestCase;

use Psr\Http\Message\ResponseInterface;

use Caldera\Http\Client;
use Caldera\Http\Request;

class ClientTest extends TestCase {

	public function testClientBase() {
		$client = new Client();
		#
		$request = new Request('get', 'https://caldera.vecode.net/test/auth');
		$response = $client->sendRequest($request);
		$this->assertInstanceOf(ResponseInterface::class, $response);
		$this->assertEquals(403, $response->getStatusCode());
		#
		$request = new Request('get', 'https://caldera.vecode.net/test/auth');
		$request = $request->withHeader('Authorization', 'Bearer f7fb5ed3fd1ccfa7efdcdb461bdd3089');
		$response = $client->sendRequest($request);
		$this->assertInstanceOf(ResponseInterface::class, $response);
		$this->assertEquals(201, $response->getStatusCode());
		$this->assertEquals(['application/json'], $response->getHeader('Content-Type'));
		$parsed = json_decode( $response->getBody()->getContents() );
		$this->assertEquals('Bearer f7fb5ed3fd1ccfa7efdcdb461bdd3089', $parsed->data->auth);
		#
		$client->setCertificateBundle('');
		$request = new Request('get', 'https://caldera.vecode.net/test/auth');
		$this->expectException(RuntimeException::class);
		$response = $client->sendRequest($request);
	}

	public function testClientGet() {
		# Specify the certificate bundle and cookie jar path
		$cainfo = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'cacert.pem';
		$cookie_jar = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'output' . DIRECTORY_SEPARATOR . 'cookies';
		#
		$client = new Client();
		$client->setCertificateBundle($cainfo)
			->setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:99.0) Gecko/20100101 Firefox/99.0')
			->setRedirect(true)
			->setReferer('https://vecode.net')
			->setTimeout(5)
			->setCookieJar($cookie_jar);
		$this->assertEquals(5, $client->getTimeout());
		$this->assertEquals(true, $client->getRedirect());
		$this->assertEquals($cookie_jar, $client->getCookieJar());
		$this->assertEquals('Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:99.0) Gecko/20100101 Firefox/99.0', $client->getUserAgent());
		$this->assertEquals('https://vecode.net', $client->getReferer());
		$this->assertEquals($cainfo, $client->getCertificateBundle());
		#
		$response = $client->get('https://caldera.vecode.net/test/auth');
		$this->assertInstanceOf(ResponseInterface::class, $response);
		$this->assertEquals(403, $response->getStatusCode());
		#
		$response = $client->get('https://caldera.vecode.net/test/auth', [
			'headers' => [
				'Authorization' => 'Bearer f7fb5ed3fd1ccfa7efdcdb461bdd3089',
			],
		]);
		$this->assertInstanceOf(ResponseInterface::class, $response);
		$this->assertEquals(201, $response->getStatusCode());
		$this->assertEquals(['application/json'], $response->getHeader('Content-Type'));
		$parsed = json_decode( $response->getBody()->getContents() );
		$this->assertEquals('GET', $parsed->data->method);
		$this->assertEquals('Bearer f7fb5ed3fd1ccfa7efdcdb461bdd3089', $parsed->data->auth);
	}

	public function testClientPost() {
		$client = new Client();
		#
		$response = $client->post('https://caldera.vecode.net/test/echo', [
			'fields' => [
				'foo' => 'bar',
				'bar' => [
					'baz',
					'qux',
				],
			],
		]);
		$this->assertInstanceOf(ResponseInterface::class, $response);
		$this->assertEquals(202, $response->getStatusCode());
		$this->assertEquals(['application/json'], $response->getHeader('Content-Type'));
		$parsed = json_decode( $response->getBody()->getContents() );
		$this->assertEquals('POST', $parsed->data->method);
		#
		$response = $client->post('https://caldera.vecode.net/test/echo', [
			'json' => [
				'id' => 42,
				'foo' => 'bar',
			]
		]);
		$this->assertEquals(202, $response->getStatusCode());
		$this->assertEquals(['application/json'], $response->getHeader('Content-Type'));
		$parsed = json_decode( $response->getBody()->getContents() );
		$this->assertEquals('POST', $parsed->data->method);
		$this->assertEquals(['application/json'], $parsed->data->headers->{'Content-Type'});
	}

	public function testClientPut() {
		$client = new Client();
		#
		$response = $client->put('https://caldera.vecode.net/test/echo', [
			'json' => [
				'id' => 42,
				'foo' => 'bar',
			]
		]);
		$this->assertInstanceOf(ResponseInterface::class, $response);
		$this->assertEquals(202, $response->getStatusCode());
		$this->assertEquals(['application/json'], $response->getHeader('Content-Type'));
		$parsed = json_decode( $response->getBody()->getContents() );
		$this->assertEquals('PUT', $parsed->data->method);
		$this->assertEquals(['application/json'], $parsed->data->headers->{'Content-Type'});
	}

	public function testClientPatch() {
		$client = new Client();
		#
		$response = $client->patch('https://caldera.vecode.net/test/echo', [
			'json' => [
				'id' => 42,
				'foo' => 'bar',
			]
		]);
		$this->assertInstanceOf(ResponseInterface::class, $response);
		$this->assertEquals(202, $response->getStatusCode());
		$this->assertEquals(['application/json'], $response->getHeader('Content-Type'));
		$parsed = json_decode( $response->getBody()->getContents() );
		$this->assertEquals('PATCH', $parsed->data->method);
		$this->assertEquals(['application/json'], $parsed->data->headers->{'Content-Type'});
	}

	public function testClientDelete() {
		$client = new Client();
		#
		$response = $client->delete('https://caldera.vecode.net/test/echo', [
			'json' => [
				'id' => [1, 3, 5, 7, 9]
			]
		]);
		$this->assertInstanceOf(ResponseInterface::class, $response);
		$this->assertEquals(202, $response->getStatusCode());
		$this->assertEquals(['application/json'], $response->getHeader('Content-Type'));
		$parsed = json_decode( $response->getBody()->getContents() );
		$this->assertEquals('DELETE', $parsed->data->method);
		$this->assertEquals(['application/json'], $parsed->data->headers->{'Content-Type'});
	}

	public function testClientOptions() {
		$client = new Client();
		#
		$response = $client->options('https://caldera.vecode.net/test/echo');
		$this->assertInstanceOf(ResponseInterface::class, $response);
		$this->assertEquals(202, $response->getStatusCode());
		$this->assertEquals(['application/json'], $response->getHeader('Content-Type'));
		$parsed = json_decode( $response->getBody()->getContents() );
		$this->assertEquals('OPTIONS', $parsed->data->method);
	}

	public function testClientHead() {
		$client = new Client();
		#
		$response = $client->head('https://caldera.vecode.net/test/echo');
		$this->assertInstanceOf(ResponseInterface::class, $response);
		$this->assertEquals(202, $response->getStatusCode());
	}

	public function testClientDownload() {
		$client = new Client();
		#
		$download = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'output' . DIRECTORY_SEPARATOR . 'response.json';
		$response = $client->get('https://caldera.vecode.net/test/echo', [
			'download' => $download
		]);
		$this->assertInstanceOf(ResponseInterface::class, $response);
		$this->assertFileExists($download);
	}

	public function testClientUpload() {
		$client = new Client();
		#
		$upload = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'client_upload.md';
		$response = $client->post('https://caldera.vecode.net/test/echo', [
			'files' => [
				'upload' => $upload
			]
		]);
		$this->assertInstanceOf(ResponseInterface::class, $response);
		$this->assertEquals(202, $response->getStatusCode());
		$parsed = json_decode( $response->getBody()->getContents() );
		$this->assertStringStartsWith('multipart/form-data; boundary=', $parsed->data->headers->{'Content-Type'}[0]);
	}
}
