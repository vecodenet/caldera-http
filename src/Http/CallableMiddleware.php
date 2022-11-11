<?php

declare(strict_types = 1);

/**
 * Caldera HTTP
 * HTTP message interface, handlers and factories implementation, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Http;

use Closure;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CallableMiddleware implements MiddlewareInterface {

	/**
	 * Callback
	 * @var Closure
	 */
	protected $callback;

	/**
	 * Constructor
	 * @param Closure $callback Middleware callback
	 */
	public function __construct(Closure $callback) {
		$this->callback = $callback;
	}

	/**
	 * Process request
	 * @param  ServerRequestInterface  $request The request instance
	 * @param  RequestHandlerInterface $handler The handler instance
	 * @return ResponseInterface
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		return call_user_func($this->callback, $request, $handler);
	}
}
