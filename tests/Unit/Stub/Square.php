<?php

declare(strict_types=1);

namespace PhpDeal\Tests\Unit\Stub;

use PhpDeal\Attribute\Ensure;
use PhpDeal\Attribute\Inherit;
use PhpDeal\Attribute\Invariant;
use PhpDeal\Attribute\Verify;
use Stringable;

#[Invariant('$this->sides === 4')]
final class Square extends Polygon implements Stringable
{
    protected int $sides = 4;

    private int $size = 2;

    #[Verify('$factor !== 13.0')]
    #[Ensure('$__result >= $this->size')]
    public function scale(float $factor): float
    {
        return $factor * $this->size;
    }

    #[Inherit]
    public function resize(float $factor): float
    {
        return $factor;
    }

    /**
     * {@inheritDoc}
     */
    #[Verify('true')]
    public function documented(): void {}

    #[Verify('$secret > 0')]
    public function hidden(int $secret): void {}

    #[Verify('$a > 0')]
    public function withParameters(int $a, int $b = 5, int ...$rest): void {}

    #[Verify('$__result')]
    public static function create(): self
    {
        return new self();
    }

    public function __toString(): string
    {
        return 'square';
    }
}
