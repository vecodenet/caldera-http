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

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Container\ContainerInterface;

use Caldera\Http\CallableMiddleware;
use Caldera\Http\CallableRequestHandler;

trait HasMiddleware {

	/**
	 * Middleware to include
	 * @var array
	 */
	protected $with = [];

	/**
	 * Middleware to exclude
	 * @var array
	 */
	protected $without = [];

	/**
	 * Add middleware to include
	 * @param  mixed $middleware Middleware to include
	 * @return $this
	 */
	public function with($middleware) {
		if ( $middleware instanceof Closure ) {
			$middleware = new CallableMiddleware($middleware);
		}
		if ( is_array($middleware) ) {
			$this->with = array_merge($this->with, $middleware);
		} else {
			$this->with[] = $middleware;
		}
		return $this;
	}

	/**
	 * Add middleware to exclude
	 * @param  mixed $middleware Middleware to exclude
	 * @return $this
	 */
	public function without($middleware) {
		if ( is_array($middleware) ) {
			$this->without = array_merge($this->without, $middleware);
		} else {
			$this->without[] = $middleware;
		}
		return $this;
	}

	/**
	 * Get middleware stack
	 * @return array
	 */
	public function getStack(): array {
		$stack = array_udiff($this->with, $this->without, function($a, $b) {
			$a = is_object($a) ? get_class($a) : $a;
			$b = is_object($b) ? get_class($b) : $b;
			return strcasecmp($a, $b);
		});
		return $stack;
	}

	/**
	 * Get middleware to include
	 * @return array
	 */
	public function getWith(): array {
		return $this->with;
	}

	/**
	 * Get middleware to exclude
	 * @return array
	 */
	public function getWithout(): array {
		return $this->without;
	}

	/**
	 * Dispatch middleware
	 * @param  ServerRequestInterface $request   Request instance
	 * @param  ContainerInterface     $container Container instance
	 * @param  Closure                $default   Default handler
	 * @return ResponseInterface
	 */
	public function dispatch(ServerRequestInterface $request, ContainerInterface $container, Closure $default): ResponseInterface {
		$stack = $this->getStack();
		$chain = new CallableRequestHandler($default);
		foreach (array_reverse($stack) as $middleware) {
			if (! is_object($middleware) ) {
				$instance = $container->get($middleware);
			} else {
				$instance = $middleware;
			}
			$chain = new MiddlewareHandler($instance, $chain);
		}
		return $chain->handle($request);
	}
}
