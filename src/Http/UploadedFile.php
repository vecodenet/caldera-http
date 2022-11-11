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
use RuntimeException ;
use InvalidArgumentException;

use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\StreamInterface;

use Caldera\Http\Stream;

class UploadedFile implements UploadedFileInterface {

	/**
	 * Error array
	 * @var array
	 */
	protected static $errors = [
		UPLOAD_ERR_OK,
		UPLOAD_ERR_INI_SIZE,
		UPLOAD_ERR_FORM_SIZE,
		UPLOAD_ERR_PARTIAL,
		UPLOAD_ERR_NO_FILE,
		UPLOAD_ERR_NO_TMP_DIR,
		UPLOAD_ERR_CANT_WRITE,
		UPLOAD_ERR_EXTENSION,
	];

	/**
	 * Client file name
	 * @var string
	 */
	protected $clientFilename = '';

	/**
	 * Client media type
	 * @var string
	 */
	protected $clientMediaType = '';

	/**
	 * Error code
	 * @var int
	 */
	protected $error = 0;

	/**
	 * Moved flag
	 * @var bool
	 */
	protected $moved = false;

	/**
	 * File array
	 * @var mixed
	 */
	protected $file;

	/**
	 * File size
	 * @var int
	 */
	protected $size = 0;

	/**
	 * StreamInterface instance
	 * @var StreamInterface
	 */
	protected $stream = null;

	/**
	 * Constructor
	 * @param mixed $streamOrFile    Stream or file
	 * @param int   $size            Size
	 * @param int   $errorStatus     Error code
	 * @param mixed $clientFilename  Client file name
	 * @param mixed $clientMediaType Client media type
	 */
	public function __construct($streamOrFile, $size, $errorStatus, $clientFilename = null, $clientMediaType = null) {
		if (! is_int($errorStatus) || ! isset( self::$errors[$errorStatus] ) ) {
			throw new InvalidArgumentException('Upload file error status must be an integer value and one of the "UPLOAD_ERR_*" constants.');
		}
		if (! is_int($size) ) {
			throw new InvalidArgumentException('Upload file size must be an integer');
		}
		if ($clientFilename !== null && !is_string($clientFilename)) {
			throw new InvalidArgumentException('Upload file client filename must be a string or null');
		}
		if ($clientMediaType !== null && !is_string($clientMediaType)) {
			throw new InvalidArgumentException('Upload file client media type must be a string or null');
		}
		$this->error = $errorStatus;
		$this->size = $size;
		$this->clientFilename = $clientFilename;
		$this->clientMediaType = $clientMediaType;
		if ($this->error === UPLOAD_ERR_OK) {
			// Depending on the value set file or stream variable.
			if ( is_string($streamOrFile) ) {
				$this->file = $streamOrFile;
			} else if (is_resource($streamOrFile)) {
				$this->stream = new Stream($streamOrFile);
			} else if ($streamOrFile instanceof StreamInterface) {
				$this->stream = $streamOrFile;
			} else {
				throw new InvalidArgumentException('Invalid stream or file provided for UploadedFile');
			}
		}
	}

	/**
	 * Retrieve a stream representing the uploaded file
	 * @return StreamInterface Stream representation of the uploaded file
	 */
	public function getStream() {
		$ret = null;
		$this->check();
		if ($this->stream != null && $this->stream instanceof StreamInterface) {
			$ret = $this->stream;
		} else {
			try {
				$ret = new Stream(fopen($this->file, 'r'));
			} catch (Exception $e) {
				throw new RuntimeException(sprintf('The file "%s" cannot be opened.', $this->file));
			}
		}
		return $ret;
	}

	/**
	 * Move the uploaded file to a new location
	 * @param  string $targetPath Path to which to move the uploaded file
	 * @return void
	 */
	public function moveTo($targetPath) {
		$this->check();
		if (!is_string($targetPath) || $targetPath === '') {
			throw new InvalidArgumentException('Invalid path provided for move operation; must be a non-empty string');
		}
		if ($this->file !== null) {
			$this->moved = PHP_SAPI === 'cli' ? rename($this->file, $targetPath) : move_uploaded_file($this->file, $targetPath);
		} else {
			$stream = $this->getStream();
			if ( $stream->isSeekable() ) {
				$stream->rewind();
			}
			try {
				$dest = new Stream(fopen($targetPath, 'w'));
			} catch (Exception $e) {
				throw new RuntimeException(sprintf('The file "%s" cannot be opened.', $targetPath));
			}
			while (! $stream->eof() ) {
				if (! $dest->write( $stream->read(1048576) ) ) {
					break;
				}
			}
			$this->moved = true;
		}
		if (! $this->moved ) {
			throw new RuntimeException(sprintf('Uploaded file could not be moved to "%s"', $targetPath));
		}
	}

	/**
	 * Retrieve the file size
	 * @return mixed The file size in bytes
	 */
	public function getSize() {
		return $this->size;
	}

	/**
	 * Retrieve the error associated with the uploaded file
	 * @return int One of PHP's UPLOAD_ERR_XXX constants
	 */
	public function getError() {
		return $this->error;
	}

	/**
	 * Retrieve the filename sent by the client
	 * @return mixed The filename sent by the client
	 */
	public function getClientFilename() {
		return $this->clientFilename;
	}

	/**
	 * Retrieve the media type sent by the client
	 * @return mixed The media type sent by the client
	 */
	public function getClientMediaType() {
		return $this->clientMediaType;
	}

	/**
	 * Check the file status
	 * @return void
	 */
	protected function check() {
		if ($this->error !== UPLOAD_ERR_OK) {
			throw new RuntimeException('Cannot retrieve stream due to upload error');
		}
		if ($this->moved) {
			throw new RuntimeException('Cannot retrieve stream after it has already been moved');
		}
	}
}