<?php

declare(strict_types=1);

namespace PhpDeal\Tests\Fixtures\Ensure;

use PhpDeal\Attribute\Ensure;

abstract class ParentCounter extends Grandparent
{
    #[Ensure('$this->value !== 2')]
    public function add(int $value): void
    {
        $this->value += $value;
    }
}
