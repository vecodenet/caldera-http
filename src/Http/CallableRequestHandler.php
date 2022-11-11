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

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CallableRequestHandler implements RequestHandlerInterface {

	/**
	 * Callback
	 * @var Closure
	 */
	protected $callback;

	/**
	 * Constructor
	 * @param Closure $callback RequestHandler callback
	 */
	function __construct(Closure $callback) {
		$this->callback = $callback;
	}

	/**
	 * Handle request
	 * @param  ServerRequestInterface $request Request instance
	 * @return ResponseInterface
	 */
	public function handle(ServerRequestInterface $request): ResponseInterface {
		return call_user_func($this->callback, $request);
	}
}
