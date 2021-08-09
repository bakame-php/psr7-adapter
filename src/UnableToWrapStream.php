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

use Psr\Http\Message\StreamInterface;
use RuntimeException;

final class UnableToWrapStream extends RuntimeException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function dueToStreamPermission(): self
    {
        return new self('The '.StreamInterface::class.' instance must be readable, writable or both.');
    }

    public static function dueToUnsupportedStream(): self
    {
        return new self('The '.StreamInterface::class.' instance could not be converted into a PHP stream resource.');
    }
}
