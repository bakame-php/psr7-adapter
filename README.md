Bakame PSR-7 Adapter
=====

This package enables converting a [PSR-7 StreamInterface objects](//www.php-fig.org/psr/psr-7/) into a PHP stream. This make it possible to work with functions and class which expect a PHP stream resource like [League CSV object](//csv.thephpleague.com) package.

The included `StreamWrapper` class is heavily inspired/copied from the excellent [Guzzle/Psr7](https://github.com/guzzle/psr7#php-streamwrapper) package written by Michael Dowling which uses the [MIT License](https://github.com/guzzle/psr7/blob/master/LICENSE)

Requirements
-------

You need **PHP >= 7.3** but the latest stable version of PHP is recommended.
- A [PSR-7](//packagist.org/providers/psr/http-message-implementation) http mesage implementation ([Diactoros](//github.com/zendframework/zend-diactoros), [Guzzle](//github.com/guzzle/psr7), [Slim](//github.com/slimphp/Slim), etc...)

Installation
-------

Install `bakame/psr7-adapter` using Composer.

```
$ composer require bakame/psr7-adapter
```

Documentation
------

### StreamWrapper::streamToResource
### StreamWrapper::streamToAppendableResource

```php
<?php

use Psr\Http\Message\StreamInterface;
use Bakame\Psr7\Adapter\StreamWrapper;

public static StreamWrapper::streamToResource(StreamInterface $stream): resource;
public static StreamWrapper::streamToAppendableResource(StreamInterface $stream): resource;
```

returns a PHP stream resource from a PSR-7 `StreamInterface` object.

#### Parameters

- `$stream` : a object implementing PSR-7 `StreamInterface` interface.

#### Returned values

A PHP stream resource

#### Exception

A `Bakame\Psr7\Adapter\UnableToWrapStream` will be triggered when the following situations are encountered:

- If the `StreamInterface` is not readable and writable
- If the stream resource could not be created.

Usage example
------

Here's a simple usage with `League\Csv`.

```php
<?php

use Bakame\Psr7\Adapter\StreamWrapper;
use League\Csv\Reader;
use League\Csv\Writer;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

final class CsvDelimiterSwitcherAction
{
    public function __construct(
        private string $inputDelimiter,
        private string $outputDelimiter
    ) {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        //let's create a CSV Reader object from the submitted file
        $inputCsv = $request->getUploadedFiles()['csv'];
        $reader = Reader::createFromStream(
            StreamWrapper::streamToResource($inputCsv->getStream())
        );
        $reader->setDelimiter($inputDelimiter);
        
        $psr7stream = $response->getBody();
        $psr7stream->write($reader->getInputBOM());
    
        //let's create a CSV Writer object from the response body
        //because we already wrote to the stream we need to have an appendable resource 
        $writer = Writer::createFromStream(
            StreamWrapper::streamToAppendableResource($psr7stream);
        );
        //we convert the delimiter
        $writer->setDelimiter($outputDelimiter);
        $writer->insertAll($reader);
    
        //we add CSV header to enable downloading the converter document
        return $response
            ->withHeader('Content-Type', 'text/csv, charset=utf-8')
            ->withHeader('Content-Transfer-Encoding', 'binary')
            ->withHeader('Content-Description', 'File Transfer')
            ->withHeader('Content-Disposition', 'filename=csv-'.(new DateTimeImmutable())->format('Ymdhis').'.csv')
        ;
    }
}
```

In both cases, the `StreamInterface` objects are never detached or removed from their parent objects (ie the `Request` object or the `Response` object), the CSV objects operate on their `StreamInterface` property using the adapter stream returned by `resource_from`.

Testing
-------

The package has:

- a [PHPUnit](https://phpunit.de) test suite
- a coding style compliance test suite using [PHP CS Fixer](https://cs.symfony.com/).
- a code analysis compliance test suite using [PHPStan](https://github.com/phpstan/phpstan).

To run the tests, run the following command from the project folder.

``` bash
$ composer test
```

Contributing
-------

Contributions are welcome and will be fully credited. Please see [CONTRIBUTING](.github/CONTRIBUTING.md) and [CONDUCT](CONDUCT.md) for details.

Security
-------

If you discover any security related issues, please email nyamsprod@gmail.com instead of using the issue tracker.

Credits
-------

- [ignace nyamagana butera](https://github.com/nyamsprod)
- [All Contributors](https://github.com/bakame-php/psr7-csv-factory/graphs/contributors)

License
-------

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.

[PSR-2]: http://www.php-fig.org/psr/psr-2/
[PSR-4]: http://www.php-fig.org/psr/psr-4/
