<?php

namespace BakameTest\Psr7\Csv;

use InvalidArgumentException;
use League\Csv\Reader;
use League\Csv\Writer;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

/**
 * @coversDefaultClass Bakame\Psr7\csv_from_stream
 */
class FunctionTest extends TestCase
{
    public function testCreateReaderFromStreamInterface()
    {
        $stream = $this
            ->getMockBuilder(StreamInterface::class)
            ->setMethods(['isSeekable', 'isReadable', 'isWritable', 'eof'])
            ->getMockForAbstractClass()
        ;

        $stream->method('isSeekable')->willReturn(true);
        $stream->method('isWritable')->willReturn(false);
        $stream->method('isReadable')->willReturn(true);
        $stream->method('eof')->willReturn(true);

        $this->assertInstanceOf(Reader::class, \Bakame\Psr7\Csv\csv_from_stream(Reader::class, $stream));
    }

    public function testCreateWriterFromStreamInterface()
    {
        $stream = $this
            ->getMockBuilder(StreamInterface::class)
            ->setMethods(['isSeekable', 'isReadable', 'isWritable', 'eof'])
            ->getMockForAbstractClass()
        ;

        $stream->method('isSeekable')->willReturn(true);
        $stream->method('isWritable')->willReturn(true);
        $stream->method('isReadable')->willReturn(false);
        $stream->method('eof')->willReturn(true);

        $this->assertInstanceOf(Writer::class, \Bakame\Psr7\Csv\csv_from_stream(Writer::class, $stream));
    }

    public function testCreateFromStreamInterfaceThrowsException()
    {
        $stream = $this
            ->getMockBuilder(StreamInterface::class)
            ->setMethods(['isSeekable', 'isReadable', 'isWritable', 'eof'])
            ->getMockForAbstractClass()
        ;

        $stream->method('isSeekable')->willReturn(true);
        $stream->method('isWritable')->willReturn(true);
        $stream->method('isReadable')->willReturn(false);
        $stream->method('eof')->willReturn(true);

        $this->expectException(InvalidArgumentException::class);
        \Bakame\Psr7\Csv\csv_from_stream('reader', $stream);
    }

    public function testGetResourceThrowsExceptionIfStreamInterfaceIsNotSeekable()
    {
        $stream = $this
            ->getMockBuilder(StreamInterface::class)
            ->setMethods(['isSeekable', 'isReadable', 'isWritable', 'eof'])
            ->getMockForAbstractClass()
        ;

        $stream->method('isSeekable')->willReturn(false);
        $stream->method('isWritable')->willReturn(true);
        $stream->method('isReadable')->willReturn(true);
        $stream->method('eof')->willReturn(true);

        $this->expectException(InvalidArgumentException::class);
        \Bakame\Psr7\Csv\csv_from_stream(Reader::class, $stream);
    }

    public function testGetResourceThrowsExceptionIfStreamInterfaceIsNotReadableAndWritable()
    {
        $stream = $this
            ->getMockBuilder(StreamInterface::class)
            ->setMethods(['isSeekable', 'isReadable', 'isWritable'])
            ->getMockForAbstractClass()
        ;

        $stream->method('isSeekable')->willReturn(true);
        $stream->method('isWritable')->willReturn(false);
        $stream->method('isReadable')->willReturn(false);

        $this->expectException(InvalidArgumentException::class);
        \Bakame\Psr7\Csv\csv_from_stream(Writer::class, $stream);
    }
}
