<?php

/**
* This file is part of the bakame.psr7-csv-factory library
*
* @license http://opensource.org/licenses/MIT
* @link https://github.com/bakame-php/psr7-csv-factory
* @version 1.0.0
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace Bakame\Psr7\Csv;

use InvalidArgumentException;
use League\Csv\Reader;
use League\Csv\Writer;
use Psr\Http\Message\StreamInterface;

/**
 * returns a League\Csv\Reader or a League\Csv\Writer
 * from a StreamInterface object
 *
 * @param  string          $class  Fully Qualified name for League\Csv\Reader or League\Csv\Writer
 * @param  StreamInterface $stream
 * @return Reader|Writer
 */
function csv_from_stream($class, StreamInterface $stream)
{
    static $connection = [Reader::class => 1, Writer::class => 1];

    if (!isset($connection[$class])) {
        throw new InvalidArgumentException(sprintf('The submitted connection type `%s` is unknown', $class));
    }

    if (!$stream->isSeekable()) {
        throw new InvalidArgumentException('Argument passed must be a seekable StreamInterface object');
    }

    if (!$stream->isReadable() && !$stream->isWritable()) {
        throw new InvalidArgumentException('Argument passed must be a StreamInterface object readable, writable or both');
    }

    $mode = $stream->isReadable() ? ($stream->isWritable() ? 'r+' : 'r') : 'w';

    StreamWrapper::register();

    $stream = fopen(
        StreamWrapper::STREAM_WRAPPER_SCHEME.'://stream',
        $mode,
        null,
        stream_context_create([StreamWrapper::STREAM_WRAPPER_SCHEME => ['stream' => $stream]])
    );

    return call_user_func([$class, 'createFromStream'], $stream);
}
