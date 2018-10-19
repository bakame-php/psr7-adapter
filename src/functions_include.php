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

if (!function_exists('\Bakame\Csv\Extension\stream_from')) {
    require __DIR__.'/functions.php';
}
