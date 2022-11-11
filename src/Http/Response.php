<?php

declare(strict_types = 1);

/**
 * Caldera HTTP
 * HTTP message interface, handlers and factories implementation, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Http;

use InvalidArgumentException;

use Psr\Http\Message\ResponseInterface;

use Caldera\Http\Stream;
use Caldera\Http\Message;

class Response extends Message implements ResponseInterface {

	/**
	 * Status phrases
	 * @var array
	 */
	protected static $phrases = [
		100 => 'Continue',
		101 => 'Switching Protocols',
		102 => 'Processing',
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		207 => 'Multi-status',
		208 => 'Already Reported',
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		306 => 'Switch Proxy',
		307 => 'Temporary Redirect',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Time-out',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Large',
		415 => 'Unsupported Media Type',
		416 => 'Requested range not satisfiable',
		417 => 'Expectation Failed',
		418 => 'I\'m a teapot',
		422 => 'Unprocessable Entity',
		423 => 'Locked',
		424 => 'Failed Dependency',
		425 => 'Unordered Collection',
		426 => 'Upgrade Required',
		428 => 'Precondition Required',
		429 => 'Too Many Requests',
		431 => 'Request Header Fields Too Large',
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Time-out',
		505 => 'HTTP Version not supported',
		506 => 'Variant Also Negotiates',
		507 => 'Insufficient Storage',
		508 => 'Loop Detected',
		511 => 'Network Authentication Required'
	];

	/**
	 * Reason phrase
	 * @var string
	 */
	protected $reasonPhrase = '';

	/**
	 * Status code
	 * @var int
	 */
	protected $statusCode = 200;

	/**
	 * @param int    $status  Status code for the response, if any.
	 * @param array  $headers Headers for the response, if any.
	 * @param mixed  $body    Stream body.
	 * @param string $version Protocol version.
	 * @param string $reason  Reason phrase (a default will be used if possible).
	 */
	public function __construct($status = 200, array $headers = [], $body = null, $version = '1.1', $reason = null ) {
		$this->statusCode = $status;
		if ($body !== null) {
			$this->stream = new Stream($body);
		}
		$this->setHeaders($headers);
		if (! $reason && isset( self::$phrases[$this->statusCode] ) ) {
			$this->reasonPhrase = self::$phrases[$status];
		} else {
			$this->reasonPhrase = $reason;
		}
		$this->protocol = $version;
	}

	/**
	 * Gets the response status code
	 * @return int
	 */
	public function getStatusCode() {
		return $this->statusCode;
	}

	/**
	 * Return an instance with the specified status code and, optionally, reason phrase.
	 * @param  mixed    $code The 3-digit integer result code to set
	 * @param  string   $reasonPhrase The reason phrase
	 * @return Response
	 */
	public function withStatus($code, $reasonPhrase = '') {
		if ( !is_int($code) && !is_string($code) ) {
			throw new InvalidArgumentException('Invalid status code');
		}
		$new = clone $this;
		$new->statusCode = (int) $code;
		if (! $reasonPhrase && isset( self::$phrases[$new->statusCode] ) ) {
			$reasonPhrase = self::$phrases[$new->statusCode];
		}
		$new->reasonPhrase = $reasonPhrase;
		return $new;
	}

	/**
	 * Gets the response reason phrase associated with the status code
	 * @return string Reason phrase
	 */
	public function getReasonPhrase() {
		return $this->reasonPhrase;
	}
}