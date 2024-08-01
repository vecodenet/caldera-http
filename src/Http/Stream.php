<?php

declare(strict_types = 1);

/**
 * Caldera HTTP
 * HTTP message interface, handlers and factories implementation, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Http;

use Exception;
use RuntimeException;
use InvalidArgumentException;

use Psr\Http\Message\StreamInterface;

class Stream implements StreamInterface {

	/**
	 * Stream resource
	 * @var resource|null
	 */
	protected $stream;

	/**
	 * Seekable flag
	 * @var bool
	 */
	protected $seekable;

	/**
	 * Readable flag
	 * @var bool
	 */
	protected $readable;

	/**
	 * Writeable flag
	 * @var bool
	 */
	protected $writable;

	/**
	 * Stream URI
	 * @var mixed
	 */
	protected $uri;

	/**
	 * Stream size
	 * @var int
	 */
	protected $size;

	/**
	 * Hash of readable and writable stream types
	 * @var array
	 */
	protected static $readWriteHash = [
		'read' => [
			'r' => true, 'w+' => true, 'r+' => true, 'x+' => true, 'c+' => true,
			'rb' => true, 'w+b' => true, 'r+b' => true, 'x+b' => true,
			'c+b' => true, 'rt' => true, 'w+t' => true, 'r+t' => true,
			'x+t' => true, 'c+t' => true, 'a+' => true
		],
		'write' => [
			'w' => true, 'w+' => true, 'rw' => true, 'r+' => true, 'x+' => true,
			'c+' => true, 'wb' => true, 'w+b' => true, 'r+b' => true,
			'x+b' => true, 'c+b' => true, 'w+t' => true, 'r+t' => true,
			'x+t' => true, 'c+t' => true, 'a' => true, 'a+' => true
		]
	];

	/**
	 * Constructor
	 * @param  mixed $contents Stream contents
	 * @return StreamInterface
	 */
	public function __construct($contents = '') {
		if ( !is_string($contents) && !is_resource($contents) ) {
			throw new InvalidArgumentException('Constructor expects a string or resource');
		}
		if ( is_string($contents) ) {
			$resource = fopen('php://temp', 'rw+');
			if ($resource) {
				fwrite($resource, $contents);
				rewind($resource);
			}
			$contents = $resource;
		}
		if ( is_resource($contents) ) {
			$this->stream = $contents;
			$meta = stream_get_meta_data($this->stream);
			$this->seekable = $meta['seekable'] && fseek($this->stream, 0, SEEK_CUR) === 0;
			$this->readable = isset( self::$readWriteHash['read'][ $meta['mode'] ] );
			$this->writable = isset( self::$readWriteHash['write'][ $meta['mode'] ] );
		}
	}

	/**
	 * Destructor
	 */
	public function __destruct() {
		$this->close();
	}

	/**
	 * Reads all data from the stream into a string, from the beginning to end
	 * @return string
	 */
	public function __toString() {
		$ret = '';
		try {
			$this->seek(0);
			$ret = stream_get_contents($this->stream);
		} catch (Exception $e) {
			# Eat the exception
		}
		return $ret !== false ? $ret : '';
	}

	/**
	 * Closes the stream and any underlying resources
	 */
	public function close() {
		if ( isset($this->stream) ) {
			if ( is_resource($this->stream) ) {
				fclose($this->stream);
			}
			$this->detach();
		}
	}

	/**
	 * Separates any underlying resources from the stream
	 * @return mixed
	 */
	public function detach() {
		if (! isset($this->stream) ) {
			return null;
		}
		$result = $this->stream;
		unset($this->stream);
		$this->size = 0;
		$this->uri = null;
		$this->readable = false;
		$this->writable = false;
		$this->seekable = false;
		return $result;
	}

	/**
	 * Get the size of the stream if known
	 * @return mixed
	 */
	public function getSize() {
		if ($this->size !== null) {
			return $this->size;
		}
		if (! isset($this->stream) ) {
			return null;
		}
		if ($this->uri) {
			clearstatcache(true, $this->uri);
		}
		$stats = fstat($this->stream);
		if ( $stats ) {
			$this->size = $stats['size'];
			return $this->size;
		}
		return null;
	}

	/**
	 * Returns the current position of the file read/write pointer
	 * @return int
	 */
	public function tell() {
		$result = ftell($this->stream);
		if ($result === false) {
			throw new RuntimeException('Unable to determine stream position');
		}
		return $result;
	}

	/**
	 * Returns true if the stream is at the end of the stream
	 * @return bool
	 */
	public function eof() {
		return !$this->stream || feof($this->stream);
	}

	/**
	 * Returns whether or not the stream is seekable
	 * @return bool
	 */
	public function isSeekable() {
		return $this->seekable;
	}

	/**
	 * Seek to a position in the stream
	 * @param  int $offset Stream offset
	 * @param  int $whence Specifies how the cursor position will be calculated based on the seek offset
	 * @return void
	 */
	public function seek($offset, $whence = SEEK_SET) {
		if (! $this->seekable ) {
			throw new RuntimeException('Stream is not seekable');
		} else if (fseek($this->stream, $offset, $whence) === -1) {
			throw new RuntimeException('Unable to seek to stream position ' . $offset . ' with whence ' . var_export($whence, true));
		}
	}

	/**
	 * Seek to the beginning of the stream
	 * @return void
	 */
	public function rewind() {
		$this->seek(0);
	}

	/**
	 * Returns whether or not the stream is writable
	 * @return bool
	 */
	public function isWritable() {
		return $this->writable;
	}

	/**
	 * Write data to the stream
	 * @param string $string The string that is to be written
	 * @return int
	 */
	public function write($string) {
		if (! $this->writable ) {
			throw new RuntimeException('Cannot write to a non-writable stream');
		}
		$this->size = null;
		if (false === $result = fwrite($this->stream, $string)) {
			throw new RuntimeException('Unable to write to stream');
		}

		return $result;
	}

	/**
	 * Returns whether or not the stream is readable
	 * @return bool
	 */
	public function isReadable() {
		return $this->readable;
	}

	/**
	 * Read data from the stream
	 * @param int<0, max> $length Read up to $length bytes from the object and return them
	 * @return string
	 */
	public function read($length) {
		if (!$this->readable) {
			throw new RuntimeException('Cannot read from non-readable stream');
		}
		$ret = fread($this->stream, $length);
		return $ret ?: '';
	}

	/**
	 * Returns the remaining contents in a string
	 * @return string
	 */
	public function getContents() {
		if (! isset($this->stream) ) {
			throw new RuntimeException('Unable to read stream contents');
		}
		$contents = stream_get_contents($this->stream);
		if ($contents === false) {
			throw new RuntimeException('Unable to read stream contents');
		}
		return $contents;
	}

	/**
	 * Get stream metadata as an associative array or retrieve a specific key
	 * @param  string $key Specific metadata to retrieve
	 * @return mixed
	 */
	public function getMetadata($key = null) {
		if (! isset($this->stream) ) {
			return $key ? null : [];
		}
		$meta = stream_get_meta_data($this->stream);
		if ($key === null) {
			return $meta;
		}
		return isset( $meta[$key] ) ? $meta[$key] : null;
	}

}
