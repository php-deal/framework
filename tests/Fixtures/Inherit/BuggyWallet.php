<?php

declare(strict_types=1);

namespace PhpDeal\Tests\Fixtures\Inherit;

use PhpDeal\Attribute\Inherit;

class BuggyWallet implements Wallet
{
    private int $amount = 0;

    #[Inherit]
    public function add(int $amount): void
    {
        $this->amount += $amount * 2;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }
}
