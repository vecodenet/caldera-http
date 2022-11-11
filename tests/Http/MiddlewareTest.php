<?php

declare(strict_types = 1);

/**
 * Caldera HTTP
 * HTTP message interface, handlers and factories implementation, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Tests\Http;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use PHPUnit\Framework\TestCase;

use Caldera\Container\Container;
use Caldera\Http\CallableMiddleware;
use Caldera\Http\HasMiddleware;
use Caldera\Http\Response;
use Caldera\Http\ServerRequest;

class MiddlewareTest extends TestCase {

	public function testMiddleware() {
		$foo = new Foo();
		$foo->with(BarMiddleware::class);
		$foo->with(BazMiddleware::class);
		$foo->without(BazMiddleware::class);
		$with = $foo->getWith();
		$this->assertEquals(['Caldera\Tests\Http\BarMiddleware', 'Caldera\Tests\Http\BazMiddleware'], $with);
		$without = $foo->getWithout();
		$this->assertEquals(['Caldera\Tests\Http\BazMiddleware'], $without);
		//
		$request = new ServerRequest('get', 'https://localhost');
		$container = new Container();
		$response = $foo->dispatch($request, $container, function() {
			return new Response(201);
		});
		$this->assertEquals(201, $response->getStatusCode());
		//
		$stack = $foo->getStack();
		$this->assertEquals(['Caldera\Tests\Http\BarMiddleware'], $stack);
		//
		$foo = new Foo();
		$foo->with([BarMiddleware::class, BazMiddleware::class]);
		$foo->without([BazMiddleware::class]);
		$with = $foo->getWith();
		$this->assertEquals(['Caldera\Tests\Http\BarMiddleware', 'Caldera\Tests\Http\BazMiddleware'], $with);
		$without = $foo->getWithout();
		$this->assertEquals(['Caldera\Tests\Http\BazMiddleware'], $without);
		//
		$foo = new Foo();
		$foo->with(function(ServerRequestInterface $request, RequestHandlerInterface $handler) {
			return new Response(418);
		});
		$stack = $foo->getStack();
		$this->assertContainsOnlyInstancesOf(CallableMiddleware::class, $stack);
		//
		$request = new ServerRequest('get', 'https://localhost');
		$container = new Container();
		$response = $foo->dispatch($request, $container, function() {
			return new Response(201);
		});
		$this->assertEquals(418, $response->getStatusCode());
	}
}

class Foo {

	use HasMiddleware;
}

class BarMiddleware implements MiddlewareInterface {

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		$response = new Response(201);
		return $response;
	}
}

class BazMiddleware implements MiddlewareInterface {

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		$response = new Response(418);
		return $response;
	}
}