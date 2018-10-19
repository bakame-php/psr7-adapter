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

use League\Csv\Reader;
use League\Csv\Writer;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use function Bakame\Csv\Extension\stream_from;

/**
 * @coversDefaultClass Bakame\Csv\Extension\stream_from
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

        self::assertInstanceOf(Reader::class, Reader::createFromStream(stream_from($stream)));
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

        self::assertInstanceOf(Writer::class, Writer::createFromStream(stream_from($stream)));
    }
}
