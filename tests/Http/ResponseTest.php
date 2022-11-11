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

use Caldera\Http\Response;
use Caldera\Http\Stream;

class ResponseTest extends TestCase {

	public function testCreateResponse() {
		# Create response
		$response = new Response(201, ['Content-Type' => 'application/json'], '{}');
		$this->assertEquals( '{}', $response->getBody() );
		$this->assertEquals( 'application/json', $response->getHeaderLine('Content-Type') );
		$this->assertTrue( $response->hasHeader('Content-Type') );
		$this->assertIsArray( $response->getHeader('Content-Type') );
		$this->assertIsArray( $response->getHeaders() );
		$this->assertEquals( 201, $response->getStatusCode() );
		$this->assertEquals( '1.1', $response->getProtocolVersion() );
		$this->assertEquals( 'Created', $response->getReasonPhrase() );
	}

	public function testBuildResponse() {
		# Create stream
		$body = new Stream('{}');
		# Create response
		$response = new Response();
		$response = $response->withStatus(201);
		# Check status code and reason phrase
		$this->assertEquals( 201, $response->getStatusCode() );
		$this->assertEquals( 'Created', $response->getReasonPhrase() );
		# Add a header and a body
		$response = $response->withHeader('Content-Type', 'application/json');
		$response = $response->withBody($body);
		# Check header and body
		$this->assertInstanceOf(Response::class, $response);
		$this->assertEquals( '{}', $response->getBody() );
		$this->assertEquals( 'application/json', $response->getHeaderLine('Content-Type') );
		$this->assertTrue( $response->hasHeader('Content-Type') );
		$this->assertIsArray( $response->getHeader('Content-Type') );
		$this->assertIsArray( $response->getHeaders() );
		$this->assertEquals( '1.1', $response->getProtocolVersion() );
	}

	public function testCustomResponseCode() {
		# Create response
		$reason = "Just try again";
		$response = new Response(420, reason: $reason);
		$this->assertEquals($reason, $response->getReasonPhrase());
	}

	public function testInvalidResponseCode() {
		# Create response
		$response = new Response();
		try {
			$response->withStatus([200]);
			$this->fail('This must throw an InvalidArgumentException');
		} catch (Exception $e) {
			$this->assertInstanceOf(InvalidArgumentException::class, $e);
		}
	}
}
