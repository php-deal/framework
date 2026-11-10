<?php

declare(strict_types=1);

use PhpDeal\ContractApplication;

require \dirname(__DIR__) . '/vendor/autoload.php';

ContractApplication::getInstance()->init([
    'debug'        => true,
    'appDir'       => \dirname(__DIR__),
    'cacheDir'     => __DIR__ . '/cache',
    'includePaths' => [__DIR__ . '/Fixtures'],
]);
