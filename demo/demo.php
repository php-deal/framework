<?php
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

echo 'Deposit: 100' . PHP_EOL;
$account->deposit(100);
echo 'Current balance: ' . $account->getBalance();
echo PHP_EOL;
echo 'Withdraw: 100' . PHP_EOL;
$account->withdraw(50);
echo 'Current balance: ' . $account->getBalance();
