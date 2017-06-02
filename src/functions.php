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

namespace Bakame\Psr7\Factory;

use InvalidArgumentException;
use League\Csv\AbstractCsv;
use Psr\Http\Message\StreamInterface;

/**
 * returns a League\Csv\Reader or a League\Csv\Writer
 * from a StreamInterface object
 *
 * @param string          $class  Fully Qualified name for League\Csv\Reader or League\Csv\Writer
 * @param StreamInterface $stream
 *
 * @throws InvalidArgumentException if the class does not extends League\Csv\AbstractCsv
 * @throws InvalidArgumentException if the stream is not seekable
 *
 * @return Reader|Writer
 */
function csv_create_from_psr7($class, StreamInterface $stream)
{
    if (!is_subclass_of($class, AbstractCsv::class)) {
        throw new InvalidArgumentException(sprintf('The submitted connection type `%s` is unknown', $class));
    }

    if (!$stream->isSeekable()) {
        throw new InvalidArgumentException('Argument passed must be a seekable StreamInterface object');
    }

    return call_user_func([$class, 'createFromStream'], StreamWrapper::getResource($stream));
}
