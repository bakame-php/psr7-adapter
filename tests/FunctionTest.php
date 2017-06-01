<?php

namespace BakameTest\Psr7\Csv;

use InvalidArgumentException;
use League\Csv\Reader;
use League\Csv\Writer;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use SplFileObject;

/**
 * @coversDefaultClass Bakame\Psr7\Csv\csv_create_from_psr7
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

        $this->assertInstanceOf(Reader::class, \Bakame\Psr7\Csv\csv_create_from_psr7(Reader::class, $stream));
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

        $this->assertInstanceOf(Writer::class, \Bakame\Psr7\Csv\csv_create_from_psr7(Writer::class, $stream));
    }

    public function testThrowsExceptionIfStreamInterfaceIsNotSeekable()
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
        \Bakame\Psr7\Csv\csv_create_from_psr7(Reader::class, $stream);
    }

    public function testThrowsExceptionIfClassIsNotAbstractCsvSubclass()
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
        $this->assertInstanceOf(Writer::class, \Bakame\Psr7\Csv\csv_create_from_psr7(SplFileObject::class, $stream));
    }
}
