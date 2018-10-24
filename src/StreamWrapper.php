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

declare(strict_types=1);

namespace Bakame\Psr7\Adapter;

use Psr\Http\Message\StreamInterface;
use const FILE_APPEND;
use const SEEK_SET;
use function in_array;
use function stream_context_create;
use function stream_context_get_options;
use function stream_get_wrappers;
use function stream_wrapper_register;
use function stream_wrapper_unregister;

/**
 * StreamWrapper class to enable converting a StreamInterface instance into a PHP Stream.
 *
 * This class is heavily based on the code found in Guzzle\Psr7 package
 *
 * @link https://github.com/guzzle/psr7/blob/master/src/StreamWrapper.php
 *
 * @internal used by stream_from
 */
final class StreamWrapper
{
    const PROTOCOL = 'bakame+stream';

    /**
     * The resource context.
     *
     * @var resource
     */
    public $context;

    /**
     * The PSR-7 Stream.
     *
     * @var StreamInterface
     */
    private $stream;

    /**
     * Resource open mode.
     *
     * @var string
     */
    private $mode;

    /**
     * Unregister the class as a stream wrapper.
     */
    public static function unregister()
    {
        if (in_array(self::PROTOCOL, stream_get_wrappers(), true)) {
            stream_wrapper_unregister(self::PROTOCOL);
        }
    }

    /**
     * Register the class as a stream wrapper.
     */
    public static function register()
    {
        if (!in_array(self::PROTOCOL, stream_get_wrappers(), true)) {
            stream_wrapper_register(self::PROTOCOL, self::class);
        }
    }

    /**
     * Returns the standard path used by the stream.
     */
    public static function getStreamPath(): string
    {
        return StreamWrapper::PROTOCOL.'://stream';
    }

    /**
     * Returns the context created for the given stream object.
     *
     * @return resource
     */
    public static function createStreamContext(StreamInterface $stream)
    {
        return stream_context_create([StreamWrapper::PROTOCOL => ['stream' => $stream]]);
    }

    /**
     * Convert a PSR-7 stream into a PHP stream resource.
     *
     * @throws Exception If the conversion can not be done
     *
     * @return resource
     */
    public static function getResource(StreamInterface $stream, int $flag = 0)
    {
        if (!$stream->isReadable() && !$stream->isWritable()) {
            throw new Exception('The '.StreamInterface::class.' instance must be readable, writable or both');
        }

        $open_mode = $flag & FILE_APPEND ? 'a' : 'w';
        if ($stream->isReadable()) {
            $open_mode = $flag & FILE_APPEND ? 'a+' : 'r+';
            if (!$stream->isWritable()) {
                $open_mode = 'r';
            }
        }

        self::register();
        $stream = @fopen(self::getStreamPath(), $open_mode, false, self::createStreamContext($stream));
        if (is_resource($stream)) {
            return $stream;
        }

        throw new Exception('The '.StreamInterface::class.' instance could not be converted into a PHP stream resource');
    }

    /**
     * {@inheritdoc}
     */
    public function stream_open(string $path, string $mode, int $options, string &$opened_path = null): bool
    {
        $options = stream_context_get_options($this->context);

        if (!isset($options[self::PROTOCOL]['stream'])) {
            return false;
        }

        $this->mode = $mode;
        $this->stream = $options[self::PROTOCOL]['stream'];

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function stream_read(int $count): string
    {
        return $this->stream->read($count);
    }

    /**
     * {@inheritdoc}
     */
    public function stream_write(string $data): int
    {
        return $this->stream->write($data);
    }

    /**
     * {@inheritdoc}
     */
    public function stream_tell(): int
    {
        return $this->stream->tell();
    }

    /**
     * {@inheritdoc}
     */
    public function stream_eof(): bool
    {
        return $this->stream->eof();
    }

    /**
     * {@inheritdoc}
     */
    public function stream_seek(int $offset, int $whence = SEEK_SET): bool
    {
        $this->stream->seek($offset, $whence);

        return true;
    }

    public function stream_cast(int $cast_as)
    {
        $stream = clone $this->stream;

        return $stream->detach();
    }

    /**
     * {@inheritdoc}
     */
    public function stream_stat(): array
    {
        static $mode_map = [
            'r'  => 33060,
            'r+' => 33206,
            'w'  => 33188,
        ];

        return [
            'dev'     => 0,
            'ino'     => 0,
            'mode'    => $mode_map[$this->mode],
            'nlink'   => 0,
            'uid'     => 0,
            'gid'     => 0,
            'rdev'    => 0,
            'size'    => $this->stream->getSize() ?: 0,
            'atime'   => 0,
            'mtime'   => 0,
            'ctime'   => 0,
            'blksize' => 0,
            'blocks'  => 0,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function url_stat(string $path, int $flags): array
    {
        return [
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
        ];
    }
}
