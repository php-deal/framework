<?php

declare(strict_types=1);

namespace PhpDeal\Tests\Fixtures\Ensure;

use PhpDeal\Attribute\Ensure;

class Grandparent
{
    protected int $value = 0;

    #[Ensure('$this->value !== -1')]
    public function add(int $value): void {}
}
