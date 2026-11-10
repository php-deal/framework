<?php

declare(strict_types=1);

namespace PhpDeal\Tests\Fixtures\Verify;

use PhpDeal\Attribute\Verify;

class Grandparent
{
    #[Verify('$value !== 3')]
    public function add(int $value): void {}
}
