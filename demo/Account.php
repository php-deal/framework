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
use PhpDeal\Attribute\Inherit;

/**
 * Simple trade account: `#[Inherit]` enables every contract of {@see AccountContract}
 */
#[Inherit]
class Account implements AccountContract
{
    private float $balance = 0.0;

    public function deposit(float $amount): void
    {
        $this->balance += $amount;
    }

    public function withdraw(float $amount): void
    {
        $this->balance -= $amount;
    }

    #[Ensure('$__result === $this->balance')]
    public function getBalance(): float
    {
        return $this->balance;
    }
}
