<?php

declare(strict_types=1);

namespace PhpDeal\Tests\Fixtures\Ensure;

use PhpDeal\Attribute\Ensure;

interface PositiveA
{
    #[Ensure('$this->value !== -1')]
    public function add(int $value): void;
}
