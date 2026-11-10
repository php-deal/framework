<?php

declare(strict_types=1);

namespace PhpDeal\Tests\Fixtures\Ensure;

use PhpDeal\Attribute\Ensure;

class ChildCounter extends ParentCounter
{
    #[Ensure('$this->value !== 1')]
    public function add(int $value): void
    {
        $this->value += $value;
    }
}
