<?php

declare(strict_types=1);

namespace PhpDeal\Tests\Fixtures\Invariant;

use PhpDeal\Attribute\Invariant;

#[Invariant('$this->value !== 1')]
class ChildValue extends ParentValue
{
    public function setValue(int $value): void
    {
        $this->value = $value;
    }
}
