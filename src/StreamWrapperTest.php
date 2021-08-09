<?php

/**
 * Bakame PSR-7 Stream Adapter package
 *
 * @author Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @license http://opensource.org/licenses/MIT
 * @link https://github.com/bakame-php/csv-psr7-bridge
 * @version 1.0.0
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bakame\Psr7\Adapter;

use League\Csv\Reader;
use League\Csv\Writer;
use Nyholm\Psr7\Stream;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
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

final class StreamWrapperTest extends TestCase
{
    /** @var resource $resource */
    private $resource;

    protected function setUp(): void
    {
        /** @var resource $resource */
        $resource = tmpfile();
        $this->resource = $resource;
    }

    protected function tearDown(): void
    {
        unset($this->resource);
    }

    public function testStreamWrapper(): void
    {
        fwrite($this->resource, 'foo');
        rewind($this->resource);
        $resource = StreamWrapper::streamToResource(Stream::create($this->resource));
        self::assertSame('foo', fread($resource, 3));
        self::assertSame(3, ftell($resource));
        self::assertSame(3, fwrite($resource, 'bar'));
        self::assertSame(0, fseek($resource, 0));
        self::assertSame('foobar', fread($resource, 6));
        self::assertSame('', fread($resource, 1));
        self::assertTrue(feof($resource));
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
        ], fstat($resource));
        self::assertTrue(fclose($resource));
    }

    public function testUnregisterStream(): void
    {
        self::assertTrue(StreamWrapper::register());
        self::assertTrue(StreamWrapper::isRegistered());
        self::assertTrue(StreamWrapper::unregister());
        self::assertFalse(StreamWrapper::isRegistered());
        self::assertTrue(StreamWrapper::unregister());
    }

    public function testStreamOpenReturnsFalse(): void
    {
        $stream = new StreamWrapper();
        $stream->context = stream_context_create(['foo' => ['foo' => Stream::create($this->resource)]]);
        self::assertFalse($stream->stream_open('path', 'r', 1));
    }

    public function testItThrowsExceptionOnAppendableStreamIfStreamInterfaceIsNotReadableAndWritable(): void
    {
        $stream = $this
            ->getMockBuilder(StreamInterface::class)
            ->onlyMethods(['isReadable', 'isWritable'])
            ->getMockForAbstractClass()
        ;

        $stream->method('isWritable')->willReturn(false);
        $stream->method('isReadable')->willReturn(false);

        $this->expectException(UnableToWrapStream::class);
        StreamWrapper::streamToAppendableResource($stream);
    }

    public function testItThrowsExceptionIfStreamInterfaceIsNotReadableAndWritable(): void
    {
        $stream = $this
            ->getMockBuilder(StreamInterface::class)
            ->onlyMethods(['isReadable', 'isWritable'])
            ->getMockForAbstractClass()
        ;

        $stream->method('isWritable')->willReturn(false);
        $stream->method('isReadable')->willReturn(false);

        $this->expectException(UnableToWrapStream::class);
        StreamWrapper::streamToResource($stream);
    }

    public function testItThrowsWithInvalidFlagUsed(): void
    {
        $stream = $this
            ->getMockBuilder(StreamInterface::class)
            ->onlyMethods(['isReadable', 'isWritable', 'tell', 'eof'])
            ->getMockForAbstractClass()
        ;

        $stream->method('isWritable')->willReturn(false);
        $stream->method('isReadable')->willReturn(true);
        $stream->method('tell')->willReturn(0);
        $stream->method('eof')->willReturn(false);

        $resource = StreamWrapper::streamToAppendableResource($stream);

        self::assertSame('r', stream_get_meta_data($resource)['mode']);
    }

    public function testResourceFromWorksIfStreamInterfaceIsReadableOnly(): void
    {
        $stream = $this
            ->getMockBuilder(StreamInterface::class)
            ->onlyMethods(['isReadable', 'isWritable', 'tell', 'eof'])
            ->getMockForAbstractClass()
        ;

        $stream->method('isWritable')->willReturn(false);
        $stream->method('isReadable')->willReturn(true);
        $stream->method('tell')->willReturn(0);
        $stream->method('eof')->willReturn(false);

        $resource = StreamWrapper::streamToAppendableResource($stream);

        self::assertSame('r', stream_get_meta_data($resource)['mode']);
    }

    public function testResourceFromWorksIfStreamInterfaceIsWritableOnly(): void
    {
        $stream = $this
            ->getMockBuilder(StreamInterface::class)
            ->onlyMethods(['isReadable', 'isWritable', 'tell', 'eof'])
            ->getMockForAbstractClass()
        ;

        $stream->method('isWritable')->willReturn(true);
        $stream->method('isReadable')->willReturn(false);
        $stream->method('tell')->willReturn(0);
        $stream->method('eof')->willReturn(false);

        $rsrc = StreamWrapper::streamToAppendableResource($stream);

        self::assertSame('a', stream_get_meta_data($rsrc)['mode']);
    }

    public function testStreamCast(): void
    {
        /** @var resource $resource */
        $resource = tmpfile();
        $streams = [
            StreamWrapper::streamToResource(Stream::create($this->resource)),
            StreamWrapper::streamToResource(Stream::create($resource)),
        ];
        $write = null;
        $except = null;
        self::assertIsInt(stream_select($streams, $write, $except, 0));
    }

    public function testLeagueCsvWriter(): void
    {
        $csv = Writer::createFromStream(StreamWrapper::streamToResource(Stream::create($this->resource)));
        self::assertSame("\n", $csv->getNewline());
        $csv->setNewline("\r\n");
        $csv->insertOne(['jane', 'doe']);
        self::assertSame("jane,doe\r\n", $csv->toString());
        $csv = null;
    }

    public function testLeagueCsvReader(): void
    {
        fwrite($this->resource, 'name,surname,email'."\n".'foo,bar,baz');
        rewind($this->resource);
        $csv = Reader::createFromStream(StreamWrapper::streamToResource(Stream::create($this->resource)));
        $csv->setHeaderOffset(0);
        self::assertSame([[
            'name' => 'foo',
            'surname' => 'bar',
            'email' => 'baz',
        ]], iterator_to_array($csv, false));
        $csv = null;
    }

    public function testUrlStat(): void
    {
        $resource = StreamWrapper::streamToResource(Stream::create($this->resource));

        StreamWrapper::register();
        self::assertEquals(
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
            stat(stream_get_meta_data($resource)['uri'])
        );
    }
}
