<?php

declare(strict_types=1);

namespace PhpDeal\Tests\Fixtures\Invariant;

use PhpDeal\Attribute\Invariant;

/**
 * Physics invariant: the speed can not be negative or exceed the speed of light in vacuum
 */
#[Invariant('$this->speed < 299792458 && $this->speed >= 0')]
class Particle
{
    private float $speed;

    public function __construct(float $speed = 0)
    {
        $this->speed = $speed;
    }

    public function accelerate(float $acceleration, float $time): void
    {
        $this->speed += ($acceleration * $time);
    }

    public function decelerate(float $deceleration, float $time): void
    {
        $this->speed -= ($deceleration * $time);
    }

    public function getSpeed(): float
    {
        return $this->speed;
    }
}
