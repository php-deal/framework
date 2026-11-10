<?php

declare(strict_types=1);

namespace PhpDeal\Tests\Fixtures\Ensure;

use PhpDeal\Attribute\Ensure;

class Counter
{
    private float $value = 42;

    public int $clones = 0;

    #[Ensure('$this->value === $__old->value + $amount')]
    #[Ensure('$this->value > 0')]
    public function increment(float $amount): void
    {
        $this->value += $amount;
    }

    #[Ensure('$this->value === $__old->value + $amount')]
    public function badIncrement(float $amount): void
    {
        $this->value += ($amount - 0.1); // Dirty hacker wants to earn money!
    }

    #[Ensure('$__result === $this->value')]
    public function getValue(): float
    {
        return $this->value;
    }

    #[Ensure('$__result === $this->value')]
    public function cheatingGetter(): float
    {
        return $this->value + 1;
    }

    #[Ensure('$__result > 0')]
    public function withoutOldState(): int
    {
        return 1;
    }

    #[Ensure('\is_string($__result)')]
    public static function staticFactory(): string
    {
        return 'value';
    }

    public function __clone()
    {
        ++$this->clones;
    }
}
