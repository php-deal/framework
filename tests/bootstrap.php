<?php
declare(strict_types=1);

use PhpDeal\ContractApplication;

ContractApplication::getInstance()->init([
    'debug'    => true,
    'appDir'   => __DIR__,
    'cacheDir' => __DIR__ . '/cache/',
]);
