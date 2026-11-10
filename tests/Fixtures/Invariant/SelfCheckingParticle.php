<?php

declare(strict_types=1);

namespace PhpDeal\Tests\Fixtures\Invariant;

use PhpDeal\Attribute\Invariant;

/**
 * The invariant calls a public method of the same class, which must not trigger the invariant again
 */
#[Invariant('$this->getSpeed() >= 0')]
class SelfCheckingParticle
{
    private float $speed = 0;

    public function setSpeed(float $speed): void
    {
        $this->speed = $speed;
    }

    public function getSpeed(): float
    {
        return $this->speed;
    }
}
