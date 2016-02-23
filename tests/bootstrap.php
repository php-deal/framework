<?php

use PhpDeal\ContractApplication;

if (defined("AUTOLOAD_PATH")) {
    if (is_file(__DIR__ . '/../' .AUTOLOAD_PATH)) {
        $loader = include_once __DIR__ . '/../' . AUTOLOAD_PATH;
        $loader->addPsr4('PhpDeal\\', __DIR__);
    } else {
        throw new InvalidArgumentException("Cannot load custom autoload file located at ".AUTOLOAD_PATH);
    }
}

ContractApplication::getInstance()->init(array(
    'debug'    => true,
    'appDir'   => __DIR__,
    'cacheDir' => __DIR__.'/cache/',
));
