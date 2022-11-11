<?php

declare(strict_types = 1);

/**
 * Caldera HTTP
 * HTTP message interface, handlers and factories implementation, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Tests\Http;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use PHPUnit\Framework\TestCase;

use Caldera\Http\CallableMiddleware;
use Caldera\Http\CallableRequestHandler;
use Caldera\Http\MiddlewareHandler;
use Caldera\Http\Response;
use Caldera\Http\ServerRequest;

class HandlerTest extends TestCase {

	public function testRequestHandler() {
		$handler = new CallableRequestHandler(function(ServerRequestInterface $request) {
			return new Response(418);
		});
		$request = new ServerRequest('get', 'http://localhost');
		$response = $handler->handle($request);
		$this->assertEquals(418, $response->getStatusCode());
	}

	public function testMiddlewareHandler() {
		$middleware = new CallableMiddleware(function(ServerRequestInterface $request, RequestHandlerInterface $handler) {
			return new Response(418);
		});
		$request_handler = new CallableRequestHandler(function(ServerRequestInterface $request) {
			return new Response(201);
		});
		$middleware_handler = new MiddlewareHandler($middleware, $request_handler);
		$request = new ServerRequest('get', 'http://localhost');
		$response = $middleware_handler->handle($request);
		$this->assertEquals(418, $response->getStatusCode());
	}
}