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

declare(strict_types=1);

namespace Bakame\Psr7\Adapter;

use Psr\Http\Message\StreamInterface;
use function in_array;
use function stream_context_create;
use function stream_context_get_options;
use function stream_get_wrappers;
use function stream_wrapper_register;
use function stream_wrapper_unregister;
use const FILE_APPEND;
use const SEEK_SET;

/**
 * StreamWrapper class to enable converting a StreamInterface instance into a PHP Stream.
 *
 * This class is heavily based on the code found in Guzzle\Psr7 package
 *
 * @link https://github.com/guzzle/psr7/blob/master/src/StreamWrapper.php
 */
final class StreamWrapper
{
    private const PROTOCOL = 'bakame+stream';

    private const AVAILABLE_OPEN_MODES = [
        0 => [
            '1:' => 'r',
            ':1' => 'w',
            '1:1' => 'r+',
        ],
        FILE_APPEND => [
            '1:' => 'r',
            ':1' => 'a',
            '1:1' => 'a+',
        ],
    ];

    /** @var resource */
    public $context;

    /** @var StreamInterface */
    private $stream;

    /** @var string */
    private $mode;

    /**
     * Tell whether the class is registered as a stream wrapper.
     */
    public static function isRegistered(): bool
    {
        return in_array(self::PROTOCOL, stream_get_wrappers(), true);
    }

    /**
     * Unregister the class as a stream wrapper.
     */
    public static function unregister(): bool
    {
        if (!self::isRegistered()) {
            return true;
        }

        return stream_wrapper_unregister(self::PROTOCOL);
    }

    /**
     * Register the class as a stream wrapper.
     */
    public static function register(): bool
    {
        if (self::isRegistered()) {
            return true;
        }

        return stream_wrapper_register(self::PROTOCOL, self::class);
    }

    /**
     * Wraps a PSR-7 stream into a PHP stream resource.
     *
     * @throws UnableToWrapStream If wrapping fails or can not be done
     * @return resource
     */
    public static function streamToResource(StreamInterface $stream)
    {
        $openMode = self::AVAILABLE_OPEN_MODES[0][$stream->isReadable().':'.$stream->isWritable()] ?? null;
        if (null === $openMode) {
            throw UnableToWrapStream::dueToStreamPermission();
        }

        return StreamWrapper::toResource($stream, $openMode);
    }

    /**
     * Wraps a PSR-7 stream into a PHP stream resource.
     *
     * @throws UnableToWrapStream If wrapping fails or can not be done
     * @return resource
     */
    public static function streamToAppendableResource(StreamInterface $stream)
    {
        $openMode = self::AVAILABLE_OPEN_MODES[FILE_APPEND][$stream->isReadable().':'.$stream->isWritable()] ?? null;
        if (null === $openMode) {
            throw UnableToWrapStream::dueToStreamPermission();
        }

        return StreamWrapper::toResource($stream, $openMode);
    }


    /**
     * Returns a PHP stream resource from a PSR-7 StreamInterface object.
     *
     * @throws UnableToWrapStream If conversion fails
     *
     * @return resource
     */
    private static function toResource(StreamInterface $stream, string $openMode)
    {
        self::register();
        /** @var int|null $error */
        $error = null;
        set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) use (&$error): bool {
            if (E_WARNING === $errno) {
                $error = $errno;
            }

            return false;
        });

        $resource = fopen(self::PROTOCOL.'://stream', $openMode, false, stream_context_create([self::PROTOCOL => ['stream' => $stream]]));

        restore_error_handler();

        if (false === $resource || null !== $error) {
            throw UnableToWrapStream::dueToUnsupportedStream();
        }

        return $resource;
    }

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

    public function stream_read(int $count): string
    {
        return $this->stream->read($count);
    }

    public function stream_write(string $data): int
    {
        return $this->stream->write($data);
    }

    public function stream_tell(): int
    {
        return $this->stream->tell();
    }

    public function stream_eof(): bool
    {
        return $this->stream->eof();
    }

    public function stream_seek(int $offset, int $whence = SEEK_SET): bool
    {
        $this->stream->seek($offset, $whence);

        return true;
    }

    /**
     * @return resource|null
     */
    public function stream_cast(int $cast_as)
    {
        $stream = clone $this->stream;

        return $stream->detach();
    }

    /**
     * @return array<string, int>
     */
    public function stream_stat(): array
    {
        static $mode_map = [
            'r'  => 33060,
            'r+' => 33206,
            'w'  => 33188,
            'a'  => 33188,
            'a+' => 33188,
       ];

        return [
            'dev'     => 0,
            'ino'     => 0,
            'mode'    => $mode_map[$this->mode],
            'nlink'   => 0,
            'uid'     => 0,
            'gid'     => 0,
            'rdev'    => 0,
            'size'    => $this->stream->getSize() ?? 0,
            'atime'   => 0,
            'mtime'   => 0,
            'ctime'   => 0,
            'blksize' => 0,
            'blocks'  => 0,
        ];
    }

    /**
     * @return array<string, int>
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
