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

class StreamTest extends TestCase {

	public function testCreateFail() {
		# Create stream
		try {
			$stream = new Stream(1024);
			$stream->close();
			$this->fail('This must throw an InvalidArgumentException');
		} catch (Exception $e) {
			$this->assertInstanceOf(InvalidArgumentException::class, $e);
		}
	}

	public function testCreateStreamFromString() {
		# Create stream
		$stream = new Stream('foo');
		$this->assertEquals( 'foo', $stream->getContents() );
		$this->assertEquals( 3, $stream->getSize() );
		$this->assertEquals( 3, $stream->tell() );
		$stream->close();
	}

	public function testTryToUseAfterClosing() {
		# Create stream
		$stream = new Stream('foo');
		$this->assertEquals( 3, $stream->getSize() );
		$stream->close();
		$stream->detach();
		$this->assertEquals( null, $stream->getSize() );
		try {
			$stream->getContents();
			$this->fail('This must throw a RuntimeException');
		} catch (Exception $e) {
			$this->assertInstanceOf(RuntimeException::class, $e);
		}
		try {
			$stream->getMetadata();
			$this->fail('This must throw a RuntimeException');
		} catch (Exception $e) {
			$this->assertInstanceOf(RuntimeException::class, $e);
		}
		try {
			$stream->tell();
			$this->fail('This must throw a RuntimeException');
		} catch (Exception $e) {
			$this->assertInstanceOf(RuntimeException::class, $e);
		}
		try {
			$stream->read(3);
			$this->fail('This must throw a RuntimeException');
		} catch (Exception $e) {
			$this->assertInstanceOf(RuntimeException::class, $e);
		}
		try {
			$stream->write('Foo');
			$this->fail('This must throw a RuntimeException');
		} catch (Exception $e) {
			$this->assertInstanceOf(RuntimeException::class, $e);
		}
	}

	public function testCreateStreamFromResource() {
		# Create stream
		$path = dirname(__DIR__) . '/bootstrap.php';
		$resource = fopen($path, 'r');
		$stream = new Stream($resource);
		$this->assertStringEqualsFile( $path, $stream->getContents() );
		$stream->close();
	}

	public function testStreamToString() {
		# Create stream
		$stream = new Stream('foo');
		$this->assertEquals('foo', $stream);
		$stream->close();
	}

	public function testStreamWrite() {
		# Create stream
		$stream = new Stream();
		if ( $stream->isWritable() ) {
			$stream->write('foo');
			$this->assertEquals('foo', $stream);
		} else {
			$this->fail('This stream MUST be writeable');
		}
		$stream->close();
	}

	public function testStreamSeek() {
		# Create stream
		$content = 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Reprehenderit natus, quo quae a expedita? Unde!';
		$stream = new Stream();
		$stream->write($content);
		if ( $stream->isSeekable() && $stream->isReadable() ) {
			# Skip 6 characters and read
			$stream->seek(6);
			$chunk = $stream->read(11);
			$this->assertEquals('ipsum dolor', $chunk);
			# Skip another 40 characters and read again
			$stream->seek(40, SEEK_CUR);
			$chunk = $stream->read(19);
			$this->assertEquals('Reprehenderit natus', $chunk);
			# Try to seek past the file end
			try {
				$stream->seek(1024);
				$this->fail('This must throw a RuntimeException');
			} catch (Exception $e) {
				$this->assertInstanceOf(RuntimeException::class, $e);
			}
			# Go to the end
			$stream->seek(0, SEEK_END);
			$this->assertEquals(strlen($content), $stream->tell());
			# And back to the beginning
			$stream->rewind();
			$this->assertEquals(0, $stream->tell());
			# Now read character by character
			while (! $stream->eof() ) {
				$stream->read(1);
			}
			$this->assertTrue( $stream->eof() );
		} else {
			$this->fail('This stream MUST be seekable && readable');
		}
		$stream->close();
	}

	public function testStreamMetadata() {
		# Create stream
		$content = 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Reprehenderit natus, quo quae a expedita? Unde!';
		$stream = new Stream();
		$stream->write($content);
		$metadata = $stream->getMetadata();
		$this->assertIsArray($metadata);
		$this->assertArrayHasKey('stream_type', $metadata);
		$this->assertArrayHasKey('wrapper_type', $metadata);
		$this->assertArrayHasKey('mode', $metadata);
		$this->assertArrayHasKey('seekable', $metadata);
		$this->assertArrayHasKey('uri', $metadata);
		$stream->close();
	}

	public function testStreamUnseekable() {
		# Create stream
		$handle = fopen('http://example.com', 'r');
		$stream = new Stream($handle);
		try {
			$stream->seek(10);
			$this->fail('This must throw a RuntimeException');
		} catch (Exception $e) {
			$this->assertInstanceOf(RuntimeException::class, $e);
		}
		try {
			$stream->write('Foo');
			$this->fail('This must throw a RuntimeException');
		} catch (Exception $e) {
			$this->assertInstanceOf(RuntimeException::class, $e);
		}
		$stream->close();
	}
}
