<?php

/**
 * This file is part of the bakame.psr7-csv-factory library.
 *
 * @license http://opensource.org/licenses/MIT
 * @link https://github.com/bakame-php/psr7-csv-factory
 * @version 1.0.0
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BakameTest\Psr7\Factory;

use Bakame\Psr7\Factory\Exception;
use Bakame\Psr7\Factory\StreamWrapper;
use League\Csv\Reader;
use League\Csv\Writer;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

/**
 * @coversDefaultClass Bakame\Psr7\Factory\csv_create_from_psr7
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

        self::assertInstanceOf(Reader::class, Reader::createFromStream(StreamWrapper::getResource($stream)));
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

        self::assertInstanceOf(Writer::class, Writer::createFromStream(StreamWrapper::getResource($stream)));
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

        self::expectException(Exception::class);
        Reader::createFromStream(StreamWrapper::getResource($stream));
    }
}
