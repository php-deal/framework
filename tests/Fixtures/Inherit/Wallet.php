<?php

declare(strict_types=1);

namespace PhpDeal\Tests\Fixtures\Inherit;

use PhpDeal\Attribute\Ensure;
use PhpDeal\Attribute\Invariant;
use PhpDeal\Attribute\Verify;

#[Invariant('$this->getAmount() >= 0')]
interface Wallet
{
    #[Verify('$amount > 0')]
    #[Ensure('$this->getAmount() === $__old->getAmount() + $amount')]
    public function add(int $amount): void;

    #[Ensure('$__result >= 0')]
    public function getAmount(): int;
}
