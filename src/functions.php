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
use function fopen;
use function is_resource;
use function stream_context_create;

/**
 * Convert a PSR-7 stream into a PHP stream resource.
 *
 *
 * @throws Exception If the conversion can not be done
 *
 * @return resource
 */
function stream_from(StreamInterface $stream)
{
    if (!$stream->isReadable() && !$stream->isWritable()) {
        throw new Exception('The '.StreamInterface::class.' instance must be readable, writable or both');
    }

    $open_mode = 'w';
    if ($stream->isReadable()) {
        $open_mode = 'r+';
        if (!$stream->isWritable()) {
            $open_mode = 'r';
        }
    }

    StreamWrapper::register();
    $stream = @fopen(StreamWrapper::PROTOCOL.'://stream', $open_mode, false, stream_context_create([
        StreamWrapper::PROTOCOL => ['stream' => $stream],
    ]));

    if (is_resource($stream)) {
        return $stream;
    }

    throw new Exception('The '.StreamInterface::class.' instance could not be converted into a PHP stream resource');
}
