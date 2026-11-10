<?php

declare(strict_types=1);

namespace PhpDeal\Tests\Fixtures\Verify;

use PhpDeal\Attribute\Verify;

interface RangeA
{
    #[Verify('$value !== 2')]
    public function add(int $value): void;
}
