<?php

declare(strict_types=1);

namespace PhpDeal\Tests\Unit\Stub;

use PhpDeal\Attribute\Ensure;
use PhpDeal\Attribute\Invariant;
use PhpDeal\Attribute\Verify;

#[Invariant('$this->sides > 0')]
interface Shape
{
    #[Verify('$factor > 0')]
    #[Ensure('$__result > 0')]
    public function scale(float $factor): float;
}
