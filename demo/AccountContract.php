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

namespace Demo;

use PhpDeal\Attribute\Ensure;
use PhpDeal\Attribute\Invariant;
use PhpDeal\Attribute\Verify;

/**
 * Contract of a simple trade account: every implementation must honour it
 */
#[Invariant('$this->getBalance() >= 0')]
interface AccountContract
{
    /**
     * Deposits a positive amount of money to the account
     */
    #[Verify('$amount > 0')]
    #[Ensure('$this->getBalance() === $__old->getBalance() + $amount')]
    public function deposit(float $amount): void;

    /**
     * Withdraws money from the account, at most 50 at once and never more than the balance
     */
    #[Verify('$amount > 0 && $amount <= 50')]
    #[Verify('$amount <= $this->getBalance()')]
    #[Ensure('$this->getBalance() === $__old->getBalance() - $amount')]
    public function withdraw(float $amount): void;

    /**
     * Returns the current balance
     */
    public function getBalance(): float;
}
