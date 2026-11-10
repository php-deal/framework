<?php

declare(strict_types=1);

namespace PhpDeal\Tests\Fixtures\Invariant;

use PhpDeal\Attribute\Invariant;

#[Invariant('$this->value !== 3')]
abstract class Grandparent
{
    public int $value = 0;
}
