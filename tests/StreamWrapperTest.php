<?php

/**
 * Bakame PSR-7 Stream Adapter package.
 *
 * @author Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @license http://opensource.org/licenses/MIT
 * @link https://github.com/bakame-php/csv-psr7-bridge
 * @version 1.0.0
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BakameTest\Psr7\Adapter;

use Bakame\Psr7\Adapter\Exception;
use Bakame\Psr7\Adapter\StreamWrapper;
use League\Csv\Reader;
use League\Csv\Writer;
use PHPUnit\Framework\Error\Warning;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use function Bakame\Psr7\Adapter\stream_from;

/**
 * @coversDefaultClass Bakame\Psr7\Adapter\StreamWrapper
 */
class StreamWrapperTest extends TestCase
{
    public function testStreamWrapper()
    {
        $resource = tmpfile();
        fwrite($resource, 'foo');
        rewind($resource);
        $rsrc = stream_from(new Psr7Stream($resource));
        self::assertSame('foo', fread($rsrc, 3));
        self::assertSame(3, ftell($rsrc));
        self::assertSame(3, fwrite($rsrc, 'bar'));
        self::assertSame(0, fseek($rsrc, 0));
        self::assertSame('foobar', fread($rsrc, 6));
        self::assertSame('', fread($rsrc, 1));
        self::assertTrue(feof($rsrc));
        $stBlksize  = defined('PHP_WINDOWS_VERSION_BUILD') ? -1 : 0;
        self::assertEquals([
            'dev'     => 0,
            'ino'     => 0,
            'mode'    => 33206,
            'nlink'   => 0,
            'uid'     => 0,
            'gid'     => 0,
            'rdev'    => 0,
            'size'    => 6,
            'atime'   => 0,
            'mtime'   => 0,
            'ctime'   => 0,
            'blksize' => $stBlksize,
            'blocks'  => $stBlksize,
            0         => 0,
            1         => 0,
            2         => 33206,
            3         => 0,
            4         => 0,
            5         => 0,
            6         => 0,
            7         => 6,
            8         => 0,
            9         => 0,
            10        => 0,
            11        => $stBlksize,
            12        => $stBlksize,
        ], fstat($rsrc));
        self::assertTrue(fclose($rsrc));
    }

    public function testResourceOpeningFailed()
    {
        self::expectException(Warning::class);
        fopen(StreamWrapper::PROTOCOL.'://stream', 'r+');
    }

    /**
     * @covers Bakame\Psr7\Adapter\stream_from
     */
    public function testStreamFromThrowsExceptionIfStreamInterfaceIsNotReadableAndWritable()
    {
        $stream = $this
            ->getMockBuilder(StreamInterface::class)
            ->setMethods(['isReadable', 'isWritable'])
            ->getMockForAbstractClass()
        ;

        $stream->method('isWritable')->willReturn(false);
        $stream->method('isReadable')->willReturn(false);

        self::expectException(Exception::class);
        stream_from($stream);
    }

    /**
     * @covers Bakame\Psr7\Adapter\stream_from
     */
    public function testStreamFromWorksIfStreamInterfaceIsReadableOnly()
    {
        $stream = $this
            ->getMockBuilder(StreamInterface::class)
            ->setMethods(['isReadable', 'isWritable'])
            ->getMockForAbstractClass()
        ;

        $stream->method('isWritable')->willReturn(false);
        $stream->method('isReadable')->willReturn(true);
        self::assertInternalType('resource', stream_from($stream));
    }

    /**
     * @covers Bakame\Psr7\Adapter\stream_from
     */
    public function testStreamFromWorksIfStreamInterfaceIsWritableOnly()
    {
        $stream = $this
            ->getMockBuilder(StreamInterface::class)
            ->setMethods(['isReadable', 'isWritable'])
            ->getMockForAbstractClass()
        ;

        $stream->method('isWritable')->willReturn(true);
        $stream->method('isReadable')->willReturn(false);
        self::assertInternalType('resource', stream_from($stream));
    }

    public function testStreamCast()
    {
        $streams = [
            stream_from(new Psr7Stream(tmpfile())),
            stream_from(new Psr7Stream(tmpfile())),
        ];
        $write = null;
        $except = null;
        self::assertInternalType('integer', stream_select($streams, $write, $except, 0));
    }

    public function testLeagueCsvWriter()
    {
        $resource = tmpfile();
        $csv = Writer::createFromStream(stream_from(new Psr7Stream($resource)));
        self::assertSame("\n", $csv->getNewline());
        $csv->setNewline("\r\n");
        $csv->insertOne(['jane', 'doe']);
        self::assertSame("jane,doe\r\n", $csv->getContent());
        $csv = null;
        $resource = null;
    }

    public function testLeagueCsvReader()
    {
        $resource = tmpfile();
        fwrite($resource, 'name,surname,email'."\n".'foo,bar,baz');
        rewind($resource);
        $csv = Reader::createFromStream(stream_from(new Psr7Stream($resource)));
        $csv->setHeaderOffset(0);
        self::assertSame([[
            'name' => 'foo',
            'surname' => 'bar',
            'email' => 'baz',
        ]], iterator_to_array($csv, false));
        $csv = null;
        $resource = null;
    }
}
