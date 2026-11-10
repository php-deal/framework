<?php

declare(strict_types=1);

use PhpDeal\ContractApplication;

require \dirname(__DIR__) . '/vendor/autoload.php';

// The worked example of the Agent Skill is woven too, so that the test suite keeps it correct
ContractApplication::getInstance()->init([
    'debug'        => true,
    'appDir'       => \dirname(__DIR__),
    'cacheDir'     => __DIR__ . '/cache',
    'includePaths' => [__DIR__ . '/Fixtures', \dirname(__DIR__) . '/skills/design-by-contract/examples'],
]);
