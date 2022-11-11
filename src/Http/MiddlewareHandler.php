<?php

declare(strict_types = 1);

/**
 * Caldera HTTP
 * HTTP message interface, handlers and factories implementation, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Http;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class MiddlewareHandler implements RequestHandlerInterface {

	/**
	 * MiddlewareInterface instance
	 * @var MiddlewareInterface
	 */
	private $middleware;

	/**
	 * RequestHandlerInterface instance
	 * @var RequestHandlerInterface
	 */
	private $handler;

	/**
	 * Constructor
	 * @param MiddlewareInterface     $middleware MiddlewareInterface instance
	 * @param RequestHandlerInterface $handler    RequestHandlerInterface instance
	 */
	public function __construct(MiddlewareInterface $middleware, RequestHandlerInterface $handler) {
		$this->middleware = $middleware;
		$this->handler = $handler;
	}

	/**
	 * Handle request
	 * @param  ServerRequestInterface $request Request instance
	 * @return ResponseInterface
	 */
	public function handle(ServerRequestInterface $request): ResponseInterface {
		return $this->middleware->process($request, $this->handler);
	}
}
