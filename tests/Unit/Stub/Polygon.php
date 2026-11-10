<?php

declare(strict_types=1);

namespace PhpDeal\Tests\Unit\Stub;

use PhpDeal\Attribute\Ensure;
use PhpDeal\Attribute\Invariant;
use PhpDeal\Attribute\Verify;

#[Invariant('$this->sides >= 3')]
abstract class Polygon implements Shape
{
    protected int $sides = 3;

    #[Verify('$factor < 100')]
    #[Ensure('$__result > 0')]
    public function scale(float $factor): float
    {
        return $factor;
    }

    #[Verify('$factor > 0')]
    public function resize(float $factor): float
    {
        return $factor;
    }

    public function reveal(): void
    {
        $this->hidden(1);
    }

    /**
     * A private method is not a prototype of Square::hidden(), its contracts are not inherited
     */
    #[Verify('$secret > 0')]
    private function hidden(int $secret): void {}
}
