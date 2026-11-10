<?php

declare(strict_types=1);

namespace PhpDeal\Tests\Fixtures\Inherit;

use PhpDeal\Attribute\Inherit;

class WalletWithMethodInheritance implements Wallet
{
    private int $amount = 0;

    public int $bodyCalls = 0;

    #[Inherit]
    public function add(int $amount): void
    {
        ++$this->bodyCalls;
        $this->amount += $amount;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function forceAmount(int $amount): void
    {
        $this->amount = $amount;
    }
}
