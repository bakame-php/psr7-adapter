Bakame PSR-7 Csv Factory
=====

This package enables instantiating [League CSV object](http://csv.thephpleague.com) from [PSR-7 StreamInterface objects](http://www.php-fig.org/psr/psr-7/).

Requirements
-------

- [League\CSV](http://csv.thephpleague.com) 8.2+
- A [PSR-7](https://packagist.org/providers/psr/http-message-implementation) http mesage implementation ([Diactoros](https://github.com/zendframework/zend-diactoros), [Guzzle](https://github.com/guzzle/psr7), [Slim](https://github.com/slimphp/Slim), etc...)

Installations
-------

Install `bakame/psr7-csv-factory` using Composer.

```
$ composer require bakame/psr7-csv-factory
```

Usage
------

Here's a simple usage using `Slim\Framework` as the PSR-7 implementation.

```php
<?php

use League\Csv\Reader;
use League\Csv\Writer;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\App;
use function Bakame\Csv\Extension\stream_from;

$app = new App();
$app->post('/csv-delimiter-converter', function (Request $request, Response $response): Response {

    //let's create a CSV Reader object from the submitted file
    $input_csv = $request->getUploadedFiles()['csv'];
    $csv = Reader::createFromStream(stream_from($input_csv->getStream()));
    $csv->setDelimiter(';');

    //let's create a CSV Writer object from the response body
    $output = Writer::createFromStream(stream_from($response->getBody()));
    //we convert the delimiter from ";" to "|"
    $output->setDelimiter('|');
    $output->insertAll($csv);

    //we add CSV header to enable downloading the converter document
    return $response
        ->withHeader('Content-Type', 'text/csv, charset=utf-8')
        ->withHeader('Content-Transfer-Encoding', 'binary')
        ->withHeader('Content-Description', 'File Transfer')
        ->withHeader('Content-Disposition', 'filename=csv-'.date_create()->format('Ymdhis').'.csv')
    ;
});

$app->run();
```

In both cases, the `StreamInterface` objects are never detached or removed from their parent objects (ie the `Request` object or the `Response` object), the CSV objects operate as adapters on the `StreamInterface` object to ease retrieving and/or sending CSV documents.


### stream_from

```php
<?php

use Psr\Http\Message\StreamInterface;
use function Bakame\Csv\Extension\stream_from;

function stream_from(StreamInterface $stream): resource
```

returns a PHP stream resource from a PSR-7 `StreamInterface` object.  


#### Parameter

- `$stream` : a object implementing PSR-7 `StreamInterface` interface.

#### Returned values

A PHP stream resource

#### Exception

A `Bakame\Csv\Extension\Exception` will be triggered when the following situations are encountered:

- If the `StreamInterface` is not readable and writable
- If the stream resource could not be created.

Testing
-------

A [PHPUnit](https://phpunit.de) test suite and a coding style compliance test suite using [PHP CS Fixer](http://cs.sensiolabs.org/) are avaiable. To run the tests, run the following command from the project folder.

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
