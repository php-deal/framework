<?php

declare(strict_types=1);

namespace PhpDeal\Tests\Fixtures\Invariant;

use PhpDeal\Attribute\Invariant;

#[Invariant('$this->value !== 1')]
class InterfaceValue implements NotTwo, NotThree
{
    private int $value = 0;

    public function setValue(int $value): void
    {
        $this->value = $value;
    }
}
