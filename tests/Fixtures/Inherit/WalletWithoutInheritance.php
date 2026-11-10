<?php

declare(strict_types=1);

namespace PhpDeal\Tests\Fixtures\Inherit;

class WalletWithoutInheritance implements Wallet
{
    private int $amount = 0;

    public function add(int $amount): void
    {
        $this->amount = $amount;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }
}
