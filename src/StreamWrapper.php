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

declare(strict_types=1);

namespace Bakame\Csv\Extension;

use Psr\Http\Message\StreamInterface;
use function in_array;
use function stream_context_get_options;
use function stream_get_wrappers;
use function stream_wrapper_register;

/**
 * StreamWrapper class to enable using a
 * PSR-7 StreamInterface with League\Csv connection object.
 *
 * This class is heavily based on the code found in Guzzle\Psr7 package
 *
 * @link https://github.com/guzzle/psr7/blob/master/src/StreamWrapper.php
 *
 * @internal used by csv_create_from_stream to wrap the StreamInterface object
 */
final class StreamWrapper
{
    const PROTOCOL = 'bakame+csv';

    /**
     * the resource context.
     *
     * @var resource
     */
    public $context;

    /**
     * the PSR-7 Stream.
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
     * register the class as a stream wrapper.
     */
    public static function register()
    {
        if (!in_array(self::PROTOCOL, stream_get_wrappers(), true)) {
            stream_wrapper_register(self::PROTOCOL, self::class);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function stream_open($path, $mode, $options, &$opened_path)
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
    public function stream_read($count)
    {
        return $this->stream->read($count);
    }

    /**
     * {@inheritdoc}
     */
    public function stream_write($data)
    {
        return $this->stream->write($data);
    }

    /**
     * {@inheritdoc}
     */
    public function stream_tell()
    {
        return $this->stream->tell();
    }

    /**
     * {@inheritdoc}
     */
    public function stream_eof()
    {
        return $this->stream->eof();
    }

    /**
     * {@inheritdoc}
     */
    public function stream_seek($offset, $whence)
    {
        $this->stream->seek($offset, $whence);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function stream_stat()
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
}
