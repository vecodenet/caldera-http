<?php

declare(strict_types = 1);

/**
 * Caldera HTTP
 * HTTP message interface, handlers and factories implementation, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Tests\Http;

use Exception;
use InvalidArgumentException;
use RuntimeException;

use PHPUnit\Framework\TestCase;

use Caldera\Http\Stream;
use Caldera\Http\UploadedFile;
use Caldera\Http\Uri;

class UploadedFileTest extends TestCase {

	public function testCreateUploadedFileFail() {
		# Create UploadedFile
		try {
			$file = new UploadedFile(['foo' => 'bar'], 25, UPLOAD_ERR_OK);
			$this->fail('This must throw an InvalidArgumentException');
		} catch (Exception $e) {
			$this->assertInstanceOf(InvalidArgumentException::class, $e);
		}
	}

	public function testCreateUploadedFileFromPath() {
		# Create temp file
		$resource = tmpfile();
		$stream = new Stream($resource);
		$stream->write('{"foo":"bar"}');
		$path = $stream->getMetadata('uri');
		$size = $stream->getSize();
		# Create UploadedFile
		$file = new UploadedFile($path, $size, UPLOAD_ERR_OK, 'things.json', 'application/json');
		$this->assertInstanceOf(Stream::class, $file->getStream());
		$this->assertEquals($size, $file->getSize());
		# Check error, name and media type
		$this->assertEquals(UPLOAD_ERR_OK, $file->getError());
		$this->assertEquals('things.json', $file->getClientFilename());
		$this->assertEquals('application/json', $file->getClientMediaType());
		# Try to move the file
		$dest = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'output' . DIRECTORY_SEPARATOR . 'upload.tmp';
		$file->moveTo($dest);
		$this->assertFileExists($dest);
		unlink($dest);
		# Tro to move again
		try {
			$file->moveTo($dest);
			$this->fail('This must throw a RuntimeException');
		} catch (Exception $e) {
			$this->assertInstanceOf(RuntimeException::class, $e);
		}
	}

	public function testCreateUploadedFileFromStream() {
		# Create stream
		$stream = new Stream('{"foo":"bar"}');
		# Create UploadedFile
		$file = new UploadedFile($stream, $stream->getSize(), UPLOAD_ERR_OK, 'things.json', 'application/json');
		$this->assertInstanceOf(Stream::class, $file->getStream());
		$this->assertEquals($stream->getSize(), $file->getSize());
		# Try to move the file
		$dest = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'output' . DIRECTORY_SEPARATOR . 'upload.tmp';
		$file->moveTo($dest);
		$this->assertFileExists($dest);
		unlink($dest);
	}

	public function testCreateUploadedFileFromResource() {
		# Create resource
		$resource = tmpfile();
		$stream = new Stream($resource);
		$stream->write('{"foo":"bar"}');
		$size = $stream->getSize();
		# Create UploadedFile
		$file = new UploadedFile($resource, $size, UPLOAD_ERR_OK, 'things.json', 'application/json');
		$this->assertInstanceOf(Stream::class, $file->getStream());
		$this->assertEquals($size, $file->getSize());
		# Try to move the file
		$dest = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'output' . DIRECTORY_SEPARATOR . 'upload.tmp';
		$file->moveTo($dest);
		$this->assertFileExists($dest);
		unlink($dest);
		$stream->close($resource);
	}

	public function testInvalidSize() {
		# Create stream
		$stream = new Stream('{"foo":"bar"}',);
		# Create UploadedFile
		try {
			$file = new UploadedFile($stream, 'size', UPLOAD_ERR_OK);
			$this->fail('This must throw an InvalidArgumentException');
		} catch (Exception $e) {
			$this->assertInstanceOf(InvalidArgumentException::class, $e);
		}
	}

	public function testInvalidError() {
		# Create stream
		$stream = new Stream('{"foo":"bar"}',);
		# Create UploadedFile
		try {
			$file = new UploadedFile($stream, $stream->getSize(), 'Failed');
			$this->fail('This must throw an InvalidArgumentException');
		} catch (Exception $e) {
			$this->assertInstanceOf(InvalidArgumentException::class, $e);
		}
	}

	public function testInvalidName() {
		# Create stream
		$stream = new Stream('{"foo":"bar"}',);
		# Create UploadedFile
		try {
			$file = new UploadedFile($stream, $stream->getSize(), UPLOAD_ERR_OK, 1024);
			$this->fail('This must throw an InvalidArgumentException');
		} catch (Exception $e) {
			$this->assertInstanceOf(InvalidArgumentException::class, $e);
		}
	}

	public function testInvalidMediaType() {
		# Create stream
		$stream = new Stream('{"foo":"bar"}',);
		# Create UploadedFile
		try {
			$file = new UploadedFile($stream, $stream->getSize(), UPLOAD_ERR_OK, 'things.json', 1024);
			$this->fail('This must throw an InvalidArgumentException');
		} catch (Exception $e) {
			$this->assertInstanceOf(InvalidArgumentException::class, $e);
		}
	}

	public function testMoveToInvalidArgument() {
		# Create stream
		$stream = new Stream('{"foo":"bar"}',);
		# Create UploadedFile
		$file = new UploadedFile($stream, $stream->getSize(), UPLOAD_ERR_OK);
		try {
			$file->moveTo(1024);
			$this->fail('This must throw an InvalidArgumentException');
		} catch (Exception $e) {
			$this->assertInstanceOf(InvalidArgumentException::class, $e);
		}
	}

	public function testMoveToUploadError() {
		# Create stream
		$stream = new Stream('{"foo":"bar"}',);
		# Create UploadedFile
		$file = new UploadedFile($stream, $stream->getSize(), UPLOAD_ERR_CANT_WRITE);
		try {
			$file->moveTo(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'output');
			$this->fail('This must throw a RuntimeException');
		} catch (Exception $e) {
			$this->assertInstanceOf(RuntimeException::class, $e);
		}
	}

	public function testMoveToFileNotExists() {
		# Create resource
		$resource = tmpfile();
		$stream = new Stream($resource);
		$stream->write('{"foo":"bar"}');
		$size = $stream->getSize();
		# Create UploadedFile
		$file = new UploadedFile(__DIR__ . DIRECTORY_SEPARATOR . 'dummy.json', $size, UPLOAD_ERR_OK, 'things.json', 'application/json');
		try {
			$file->getStream();
			$this->fail('This must throw a RuntimeException');
		} catch (Exception $e) {
			$this->assertInstanceOf(RuntimeException::class, $e);
		}
		$stream->close($resource);
	}

	public function testMoveToUnwriteableTarget() {
		# Create resource
		$resource = tmpfile();
		$stream = new Stream($resource);
		$stream->write('{"foo":"bar"}');
		$size = $stream->getSize();
		# Create UploadedFile
		$file = new UploadedFile($stream, $size, UPLOAD_ERR_OK, 'things.json', 'application/json');
		# Try to move the file
		try {
			$dest = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'output';
			$file->moveTo($dest);
			$this->fail('This must throw a RuntimeException');
		} catch (Exception $e) {
			$this->assertInstanceOf(RuntimeException::class, $e);
		}
		$stream->close($resource);
	}
}
