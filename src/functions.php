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

/**
 * Convert a PSR-7 stream into a PHP stream resource.
 *
 * @see StreamWrapper::getResource
 */
function stream_from(StreamInterface $stream, int $flag = 0)
{
    return StreamWrapper::getResource($stream, $flag);
}
