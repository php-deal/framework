<?php

declare(strict_types=1);

namespace PhpDeal\Tests\Fixtures\Ensure;

use PhpDeal\Attribute\Ensure;

/**
 * Contracts of interfaces are evaluated in the scope of this class, so they can read its private state
 */
class InterfaceCounter implements PositiveA, PositiveB
{
    private int $value = 0;

    #[Ensure('$this->value !== 0')]
    public function add(int $value): void
    {
        $this->value += $value;
    }
}
