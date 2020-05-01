<?php
declare(strict_types=1);

/**
 * PHP Deal framework
 *
 * @copyright Copyright 2019, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

include __DIR__.'/../vendor/autoload.php';

include_once __DIR__.'/aspect_bootstrap.php';

$account = new Demo\Account();

echo 'Deposit: 200' . PHP_EOL;
$account->deposit(200);
echo 'Current balance: ' . $account->getBalance();
echo PHP_EOL;
echo 'Withdraw: 50' . PHP_EOL;
$account->withdraw(50);
echo 'Current balance: ' . $account->getBalance(), PHP_EOL;

echo 'current contract allows withdrawing amount less than 50' . PHP_EOL;

echo 'trying withdrawing 70 should fail' . PHP_EOL;
$account->withdraw(70);
