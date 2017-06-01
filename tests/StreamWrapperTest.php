<?php

namespace BakameTest\Psr7\Csv;

use Bakame\Psr7\Csv\StreamWrapper;
use InvalidArgumentException;
use League\Csv\Writer;
use PHPUnit\Framework\Error\Warning;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

/**
 * @coversDefaultClass Bakame\Psr7\Csv\StreamWrapper
 */
class StreamWrapperTest extends TestCase
{
    public function testLeagueCsvWriter()
    {
        $stream = new Stream(tmpfile());
        $csv = \Bakame\Psr7\Csv\csv_create_from_psr7(Writer::class, $stream);
        $this->assertSame("\n", $csv->getNewline());
        $csv->setNewline("\r\n");
        $csv->insertOne(['jane', 'doe']);
        $this->assertSame("jane,doe\r\n", (string) $csv);
        $csv = null;
        $stream = null;
    }

    public function testfstat()
    {
        $stream = $this
            ->getMockBuilder(StreamInterface::class)
            ->setMethods(['isSeekable', 'isReadable', 'isWritable'])
            ->getMockForAbstractClass()
        ;

        $stream->method('isSeekable')->willReturn(true);
        $stream->method('isWritable')->willReturn(true);
        $stream->method('isReadable')->willReturn(false);

        $resource = fopen(
            StreamWrapper::PROTOCOL.'://stream',
            'w',
            null,
            stream_context_create([StreamWrapper::PROTOCOL => ['stream' => $stream]])
        );

        $this->assertSame(33188, fstat($resource)['mode']);
    }

    public function testResourceOpeningFailed()
    {
        $this->expectException(Warning::class);
        fopen(StreamWrapper::PROTOCOL.'://stream', 'r+');
    }

    /**
     * @covers ::getResource
     */
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
        \Bakame\Psr7\Csv\csv_create_from_psr7('reader', $stream);
    }

    /**
     * @covers ::getResource
     */
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
        \Bakame\Psr7\Csv\csv_create_from_psr7(Writer::class, $stream);
    }
}
