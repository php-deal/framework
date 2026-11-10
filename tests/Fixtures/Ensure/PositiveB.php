<?php

declare(strict_types=1);

namespace PhpDeal\Tests\Fixtures\Ensure;

use PhpDeal\Attribute\Ensure;

interface PositiveB
{
    #[Ensure('$this->value !== -2')]
    public function add(int $value): void;
}
