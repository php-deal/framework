<?php

declare(strict_types=1);

namespace PhpDeal\Tests\Fixtures\Verify;

use PhpDeal\Attribute\Inherit;

class PrivateChild extends PrivateParent
{
    #[Inherit]
    public function set(int $value): void {}
}
