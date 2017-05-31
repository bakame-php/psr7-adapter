<?php

namespace BakameTest\Psr7\Csv;

use Bakame\Psr7\Csv\StreamWrapper;
use BakameTest\Psr7\Csv\Lib\Stream;
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
        $csv = \Bakame\Psr7\Csv\csv_from_stream(Writer::class, $stream);
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
            StreamWrapper::STREAM_WRAPPER_SCHEME.'://stream',
            'w',
            null,
            stream_context_create([StreamWrapper::STREAM_WRAPPER_SCHEME => ['stream' => $stream]])
        );

        $this->assertSame(33188, fstat($resource)['mode']);
    }

    public function testResourceOpeningFailed()
    {
        $this->expectException(Warning::class);
        fopen(StreamWrapper::STREAM_WRAPPER_SCHEME.'://stream', 'r+');
    }
}
