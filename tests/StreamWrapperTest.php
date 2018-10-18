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
use League\Csv\Writer;
use PHPUnit\Framework\Error\Warning;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

/**
 * @coversDefaultClass Bakame\Psr7\Factory\StreamWrapper
 */
class StreamWrapperTest extends TestCase
{
    public function testLeagueCsvWriter()
    {
        $stream = new Stream(tmpfile());
        $csv = Writer::createFromStream(StreamWrapper::getResource($stream));
        self::assertSame("\n", $csv->getNewline());
        $csv->setNewline("\r\n");
        $csv->insertOne(['jane', 'doe']);
        self::assertSame("jane,doe\r\n", (string) $csv);
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
            false,
            stream_context_create([StreamWrapper::PROTOCOL => ['stream' => $stream]])
        );

        self::assertSame(33188, fstat($resource)['mode']);
    }

    public function testResourceOpeningFailed()
    {
        self::expectException(Warning::class);
        fopen(StreamWrapper::PROTOCOL.'://stream', 'r+');
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

        self::expectException(Exception::class);
        Writer::createFromStream(StreamWrapper::getResource($stream));
    }
}
