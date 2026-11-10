<?php

/**
 * PHP Deal framework
 *
 * @copyright Copyright 2014-2026, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

use Demo\Account;
use PhpDeal\ContractApplication;
use PhpDeal\Exception\ContractViolation;

require \dirname(__DIR__) . '/vendor/autoload.php';

ContractApplication::getInstance()->init([
    'debug'        => true,
    'appDir'       => \dirname(__DIR__),
    'cacheDir'     => __DIR__ . '/cache',
    'includePaths' => [__DIR__],
    'excludePaths' => [__DIR__ . '/cache'],
]);

$account = new Account();

echo 'Deposit 200', \PHP_EOL;
$account->deposit(200);
echo 'Withdraw 50', \PHP_EOL;
$account->withdraw(50);
echo 'Balance: ', $account->getBalance(), \PHP_EOL, \PHP_EOL;

echo 'Withdraw 70, but the contract allows at most 50 at once...', \PHP_EOL;
try {
    $account->withdraw(70);
} catch (ContractViolation $violation) {
    echo \get_class($violation), ': ', $violation->getMessage(), \PHP_EOL;
    echo '  declared at ', $violation->getFile(), ':', $violation->getLine(), \PHP_EOL;
}
echo 'Balance is still: ', $account->getBalance(), \PHP_EOL;
