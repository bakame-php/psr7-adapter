<?php

/**
 * Bakame CSV PSR-7 StreamInterface bridge.
 *
 * @author Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @license http://opensource.org/licenses/MIT
 * @link https://github.com/bakame-php/csv-psr7-bridge
 * @version 1.0.0
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BakameTest\Csv\Extension;

use Bakame\Csv\Extension\Exception;
use Bakame\Csv\Extension\StreamWrapper;
use League\Csv\Writer;
use PHPUnit\Framework\Error\Warning;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use function Bakame\Csv\Extension\stream_from;

/**
 * @coversDefaultClass Bakame\Csv\Extension\StreamWrapper
 */
class StreamWrapperTest extends TestCase
{
    public function testLeagueCsvWriter()
    {
        $stream = new Psr7Stream(tmpfile());
        $csv = Writer::createFromStream(stream_from($stream));
        self::assertSame("\n", $csv->getNewline());
        $csv->setNewline("\r\n");
        $csv->insertOne(['jane', 'doe']);
        self::assertSame("jane,doe\r\n", $csv->getContent());
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
     * @covers Bakame\Csv\Extension\stream_from
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
        Writer::createFromStream(stream_from($stream));
    }
}
