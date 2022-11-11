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
use RuntimeException;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

use Caldera\Http\Request;
use Caldera\Http\Response;
use Caldera\Http\ServerRequest;
use Caldera\Http\Stream;
use Caldera\Http\UploadedFile;
use Caldera\Http\Uri;

class Factory implements RequestFactoryInterface, ResponseFactoryInterface, ServerRequestFactoryInterface, StreamFactoryInterface, UploadedFileFactoryInterface, UriFactoryInterface {

	/**
	 * Create a new request
	 * @param  string              $method The HTTP method associated with the request
	 * @param  UriInterface|string $uri    The URI associated with the request
	 * @return RequestInterface
	 */
	public function createRequest(string $method, $uri): RequestInterface {
		return new Request($method, $uri);
	}

	/**
	 *
	 * @param int                $code         HTTP status code; defaults to 200
	 * @param string             $reasonPhrase Reason phrase to associate with status code
	 * @return ResponseInterface
	 */
	public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface {
		return new Response($code, reason: $reasonPhrase);
	}

	/**
	 * Create a new server request
	 * @param string                  $method       The HTTP method associated with the request
	 * @param UriInterface|string     $uri          The URI associated with the request
	 * @param array                   $serverParams Array of SAPI parameters with which to seed the generated request instance
	 * @return ServerRequestInterface
	 */
	public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface {
		return new ServerRequest($method, $uri, serverParams: $serverParams);
	}

	/**
	 * Create request from globals
	 * @return ServerRequestInterface
	 */
	public function createServerRequestFromGlobals(): ServerRequestInterface {
		$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
		$headers = getallheaders();
		$uri = $this->getUriFromGlobals();
		$body = $this->createStreamFromFile('php://input', 'r+');
		$protocol = isset($_SERVER['SERVER_PROTOCOL']) ? str_replace('HTTP/', '', $_SERVER['SERVER_PROTOCOL']) : '1.1';
		$request = new ServerRequest($method, $uri, $headers, $body, $protocol, $_SERVER);
		$request = $request->withCookieParams($_COOKIE);
		$request = $request->withQueryParams($_GET);
		$request = $request->withParsedBody($_POST);
		$request = $request->withUploadedFiles($this->normalizeFiles($_FILES));
		return $request;
	}

	/**
	 * Create a new stream from a string
	 * @param string           $content String content with which to populate the stream
	 * @return StreamInterface
	 */
	public function createStream(string $content = ''): StreamInterface {
		return new Stream($content);
	}

	/**
	 * Create a stream from an existing file
	 * @param string           $filename Filename or stream URI to use as basis of stream
	 * @param string           $mode     Mode with which to open the underlying filename/stream
	 * @return StreamInterface
	 */
	public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface {
		if ($filename === '') {
			throw new RuntimeException('Path cannot be empty');
		}
		$resource = @fopen($filename, $mode);
		if ($resource === false) {
			if ($mode === '' || !in_array($mode[0], ['r', 'w', 'a', 'x', 'c'], true)) {
				throw new InvalidArgumentException(sprintf('The mode "%s" is invalid.', $mode));
			}
			throw new RuntimeException(sprintf('The file "%s" cannot be opened: %s', $filename, error_get_last()['message'] ?? ''));
		}
		return new Stream($resource);
	}

	/**
	 * Create a new stream from an existing resource
	 * @param resource         $resource PHP resource to use as basis of stream
	 * @return StreamInterface
	 */
	public function createStreamFromResource($resource): StreamInterface {
		return new Stream($resource);
	}

	/**
	 * @param StreamInterface        $stream          Underlying stream representing the uploaded file content
	 * @param int                    $size            Size in bytes
	 * @param int                    $error           PHP file upload error
	 * @param string                 $clientFilename  Filename as provided by the client, if any
	 * @param string                 $clientMediaType Media type as provided by the client, if any
	 * @return UploadedFileInterface
	 */
	public function createUploadedFile(StreamInterface $stream, int $size = null, int $error = UPLOAD_ERR_OK, string $clientFilename = null, string $clientMediaType = null): UploadedFileInterface {
		if ($size === null) {
			$size = $stream->getSize();
		}
		return new UploadedFile($stream, $size, $error, $clientFilename, $clientMediaType);
	}

	/**
	 * Create a new URI.
	 * @param string        $uri
	 * @return UriInterface
	 */
	public function createUri(string $uri = ''): UriInterface {
		return new Uri($uri);
	}

	/**
	 * Return an UploadedFile instance array
	 * @param  array $files An array which respect $_FILES structure
	 * @return array
	 */
	protected function normalizeFiles(array $files): array {
		$normalized = [];
		foreach ($files as $key => $value) {
			if ($value instanceof UploadedFileInterface) {
				$normalized[$key] = $value;
			} elseif (is_array($value) && isset($value['tmp_name'])) {
				$normalized[$key] = $this->createUploadedFileFromSpec($value);
			} elseif (is_array($value)) {
				$normalized[$key] = $this->normalizeFiles($value);
				continue;
			} else {
				throw new InvalidArgumentException('Invalid value in files specification');
			}
		}

		return $normalized;
	}

	/**
	 * Create and return an UploadedFile instance from a $_FILES specification
	 * @param  array $value $_FILES struct
	 * @return mixed
	 */
	protected function createUploadedFileFromSpec(array $value) {
		if (is_array($value['tmp_name'])) {
			return $this->normalizeNestedFileSpec($value);
		}
		return new UploadedFile($value['tmp_name'], (int) $value['size'], (int) $value['error'], $value['name'], $value['type']);
	}

	/**
	 * Normalize an array of file specifications
	 * @return array
	 */
	protected function normalizeNestedFileSpec(array $files = []): array {
		$normalized = [];
		foreach (array_keys($files['tmp_name']) as $key) {
			$spec = [
				'tmp_name' => $files['tmp_name'][$key],
				'size'     => $files['size'][$key],
				'error'    => $files['error'][$key],
				'name'     => $files['name'][$key],
				'type'     => $files['type'][$key],
			];
			$normalized[$key] = $this->createUploadedFileFromSpec($spec);
		}
		return $normalized;
	}

	/**
	 * Extract host and port from authority
	 * @param  string $authority Authority string
	 * @return array
	 */
	protected function extractHostAndPortFromAuthority(string $authority): array {
		$uri = 'http://' . $authority;
		$parts = parse_url($uri);
		if ($parts === false) {
			return [null, null];
		}
		$host = $parts['host'] ?? null;
		$port = $parts['port'] ?? null;
		return [$host, $port];
	}

	/**
	 * Get a Uri populated with values from $_SERVER.
	 * @return UriInterface
	 */
	protected function getUriFromGlobals(): UriInterface {
		$uri = new Uri();
		$uri = $uri->withScheme(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http');
		$has_port = false;
		if (isset($_SERVER['HTTP_HOST'])) {
			[$host, $port] = $this->extractHostAndPortFromAuthority($_SERVER['HTTP_HOST']);
			if ($host !== null) {
				$uri = $uri->withHost($host);
			}
			if ($port !== null) {
				$has_port = true;
				$uri = $uri->withPort($port);
			}
		} elseif (isset($_SERVER['SERVER_NAME'])) {
			$uri = $uri->withHost($_SERVER['SERVER_NAME']);
		} elseif (isset($_SERVER['SERVER_ADDR'])) {
			$uri = $uri->withHost($_SERVER['SERVER_ADDR']);
		}
		if (!$has_port && isset($_SERVER['SERVER_PORT'])) {
			$uri = $uri->withPort($_SERVER['SERVER_PORT']);
		}
		$has_query = false;
		if (isset($_SERVER['REQUEST_URI'])) {
			$request_uri_parts = explode('?', $_SERVER['REQUEST_URI'], 2);
			$uri = $uri->withPath($request_uri_parts[0]);
			if (isset($request_uri_parts[1])) {
				$has_query = true;
				$uri = $uri->withQuery($request_uri_parts[1]);
			}
		}
		if (!$has_query && isset($_SERVER['QUERY_STRING'])) {
			$uri = $uri->withQuery($_SERVER['QUERY_STRING']);
		}
		return $uri;
	}
}