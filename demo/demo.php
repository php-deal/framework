<?php
/**
 * PHP Deal framework
 *
 * @copyright Copyright 2014, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

/** @var Composer\Autoload\ClassLoader $loader */
$loader = include __DIR__.'/../vendor/autoload.php';
$loader->add('Demo', __DIR__);

include_once __DIR__.'/aspect_bootstrap.php';

$account = new Demo\Account();
$account->deposit(100);
echo $account->getBalance();
