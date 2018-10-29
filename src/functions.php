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
use function sprintf;
use const FILE_APPEND;

/**
 * Convert a PSR-7 stream into a PHP stream resource.
 *
 * @throws Exception If the conversion can not be done
 *
 * @return resource
 */
function resource_from(StreamInterface $stream, int $flag = 0)
{
    static $open_mode_list = [
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

    $open_mode = $open_mode_list[$flag & FILE_APPEND][$stream->isReadable().':'.$stream->isWritable()] ?? null;
    if (null === $open_mode) {
        throw new Exception(sprintf('The %s instance must be readable, writable or both', StreamInterface::class));
    }

    StreamWrapper::register();
    $stream = @fopen(StreamWrapper::getStreamPath(), $open_mode, false, StreamWrapper::createStreamContext($stream));
    if (is_resource($stream)) {
        return $stream;
    }

    throw new Exception(sprintf('The %s instance could not be converted into a PHP stream resource', StreamInterface::class));
}
