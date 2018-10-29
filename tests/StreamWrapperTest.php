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
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use function Bakame\Psr7\Adapter\resource_from;
use function defined;
use function fclose;
use function feof;
use function fread;
use function fseek;
use function fstat;
use function ftell;
use function fwrite;
use function iterator_to_array;
use function rewind;
use function stat;
use function stream_context_create;
use function stream_get_meta_data;
use function stream_select;
use function tmpfile;
use const FILE_APPEND;

class StreamWrapperTest extends TestCase
{
    public function testStreamWrapper()
    {
        $resource = tmpfile();
        fwrite($resource, 'foo');
        rewind($resource);
        $rsrc = resource_from(new Psr7Stream($resource));
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

    public function testUnregisterStream()
    {
        self::assertTrue(StreamWrapper::register());
        self::assertTrue(StreamWrapper::isRegistered());
        self::assertTrue(StreamWrapper::unregister());
        self::assertFalse(StreamWrapper::isRegistered());
        self::assertTrue(StreamWrapper::unregister());
    }

    public function testStreamOpenReturnsFalse()
    {
        $stream = new StreamWrapper();
        $stream->context = stream_context_create(['foo' => ['foo' => new Psr7Stream(tmpfile())]]);
        self::assertFalse($stream->stream_open('path', 'r', 1));
    }

    public function testResourceFromThrowsExceptionIfStreamInterfaceIsNotReadableAndWritable()
    {
        $stream = $this
            ->getMockBuilder(StreamInterface::class)
            ->setMethods(['isReadable', 'isWritable'])
            ->getMockForAbstractClass()
        ;

        $stream->method('isWritable')->willReturn(false);
        $stream->method('isReadable')->willReturn(false);

        self::expectException(Exception::class);
        resource_from($stream);
    }

    public function testResourceFromWithInvalidFlagUsed()
    {
        $stream = $this
            ->getMockBuilder(StreamInterface::class)
            ->setMethods(['isReadable', 'isWritable'])
            ->getMockForAbstractClass()
        ;

        $stream->method('isWritable')->willReturn(false);
        $stream->method('isReadable')->willReturn(true);
        resource_from($stream, 22);
        self::assertInternalType('resource', resource_from($stream));
    }

    public function testResourceFromWorksIfStreamInterfaceIsReadableOnly()
    {
        $stream = $this
            ->getMockBuilder(StreamInterface::class)
            ->setMethods(['isReadable', 'isWritable'])
            ->getMockForAbstractClass()
        ;

        $stream->method('isWritable')->willReturn(false);
        $stream->method('isReadable')->willReturn(true);
        self::assertInternalType('resource', resource_from($stream));
    }

    public function testResourceFromWorksIfStreamInterfaceIsWritableOnly()
    {
        $stream = $this
            ->getMockBuilder(StreamInterface::class)
            ->setMethods(['isReadable', 'isWritable', 'tell', 'eof'])
            ->getMockForAbstractClass()
        ;

        $stream->method('isWritable')->willReturn(true);
        $stream->method('isReadable')->willReturn(false);
        $stream->method('tell')->willReturn(0);
        $stream->method('eof')->willReturn(false);

        $rsrc = resource_from($stream, FILE_APPEND);
        self::assertInternalType('resource', $rsrc);
        self::assertSame('a', stream_get_meta_data($rsrc)['mode']);
    }

    public function testStreamCast()
    {
        $streams = [
            resource_from(new Psr7Stream(tmpfile())),
            resource_from(new Psr7Stream(tmpfile())),
        ];
        $write = null;
        $except = null;
        self::assertInternalType('integer', stream_select($streams, $write, $except, 0));
    }

    public function testLeagueCsvWriter()
    {
        $resource = tmpfile();
        $csv = Writer::createFromStream(resource_from(new Psr7Stream($resource)));
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
        $csv = Reader::createFromStream(resource_from(new Psr7Stream($resource)));
        $csv->setHeaderOffset(0);
        self::assertSame([[
            'name' => 'foo',
            'surname' => 'bar',
            'email' => 'baz',
        ]], iterator_to_array($csv, false));
        $csv = null;
        $resource = null;
    }

    public function testUrlStat()
    {
        $rsrc = resource_from(new Psr7Stream(tmpfile()));

        StreamWrapper::register();
        $this->assertEquals(
            [
                'dev'     => 0,
                'ino'     => 0,
                'mode'    => 0,
                'nlink'   => 0,
                'uid'     => 0,
                'gid'     => 0,
                'rdev'    => 0,
                'size'    => 0,
                'atime'   => 0,
                'mtime'   => 0,
                'ctime'   => 0,
                'blksize' => 0,
                'blocks'  => 0,
                0         => 0,
                1         => 0,
                2         => 0,
                3         => 0,
                4         => 0,
                5         => 0,
                6         => 0,
                7         => 0,
                8         => 0,
                9         => 0,
                10        => 0,
                11        => 0,
                12        => 0,
            ],
            stat(stream_get_meta_data($rsrc)['uri'])
        );
    }
}
