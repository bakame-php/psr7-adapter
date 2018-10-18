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

declare(strict_types=1);

namespace BakameTest\Psr7\Factory;

use Psr\Http\Message\StreamInterface;
use RuntimeException;

/**
 * StreamInterface implementation heavily based on Diactoros Stream class.
 *
 * @link https://github.com/zendframework/zend-diactoros/blob/master/src/Stream.php
 */
class Stream implements StreamInterface
{
    /**
     * Underlying stream resource.
     *
     * @var resource
     */
    protected $resource;

    /**
     * {@inheritdoc}
     */
    public function __construct($resource)
    {
        $this->resource = $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        if (!$this->isReadable()) {
            return '';
        }

        try {
            $this->rewind();
            return $this->getContents();
        } catch (RuntimeException $e) {
            return '';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        if (null === $this->resource) {
            return;
        }

        $resource = $this->detach();
        if (null !== $resource) {
            fclose($resource);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function detach()
    {
        $resource = $this->resource;
        unset($this->resource);

        return $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function getSize()
    {
        if (null === $this->resource) {
            return null;
        }

        $stats = fstat($this->resource);
        return $stats['size'];
    }

    /**
     * {@inheritdoc}
     */
    public function tell()
    {
        if (null === $this->resource) {
            throw new RuntimeException('No resource available; cannot tell position');
        }

        $result = ftell($this->resource);
        if (!is_int($result)) {
            throw new RuntimeException('Error occurred during tell operation');
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function eof()
    {
        if (null === $this->resource) {
            return true;
        }

        return feof($this->resource);
    }

    /**
     * {@inheritdoc}
     */
    public function isSeekable()
    {
        if (null === $this->resource) {
            return false;
        }

        $meta = stream_get_meta_data($this->resource);
        return $meta['seekable'];
    }

    /**
     * {@inheritdoc}
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        if (null === $this->resource) {
            throw new RuntimeException('No resource available; cannot seek position');
        }

        if (!$this->isSeekable()) {
            throw new RuntimeException('Stream is not seekable');
        }

        $result = fseek($this->resource, $offset, $whence);

        if (0 !== $result) {
            throw new RuntimeException('Error seeking within stream');
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        return $this->seek(0);
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable()
    {
        if (null === $this->resource) {
            return false;
        }

        $meta = stream_get_meta_data($this->resource);
        $mode = $meta['mode'];

        return strlen($mode) != strcspn('xwca+', $mode);
    }

    /**
     * {@inheritdoc}
     */
    public function write($string)
    {
        if (null === $this->resource) {
            throw new RuntimeException('No resource available; cannot write');
        }

        if (!$this->isWritable()) {
            throw new RuntimeException('Stream is not writable');
        }

        $result = fwrite($this->resource, $string);

        if (false === $result) {
            throw new RuntimeException('Error writing to stream');
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable()
    {
        if (null === $this->resource) {
            return false;
        }

        $meta = stream_get_meta_data($this->resource);

        return 2 !== strcspn($meta['mode'], 'r+');
    }

    /**
     * {@inheritdoc}
     */
    public function read($length)
    {
        if (null === $this->resource) {
            throw new RuntimeException('No resource available; cannot read');
        }

        if (!$this->isReadable()) {
            throw new RuntimeException('Stream is not readable');
        }

        $result = fread($this->resource, $length);
        if (false === $result) {
            throw new RuntimeException('Error reading stream');
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getContents()
    {
        if (!$this->isReadable()) {
            throw new RuntimeException('Stream is not readable');
        }

        $result = stream_get_contents($this->resource);
        if (false === $result) {
            throw new RuntimeException('Error reading from stream');
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($key = null)
    {
        if (null === $key) {
            return stream_get_meta_data($this->resource);
        }

        $metadata = stream_get_meta_data($this->resource);
        if (!array_key_exists($key, $metadata)) {
            return null;
        }

        return $metadata[$key];
    }
}
