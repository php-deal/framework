<?php

declare(strict_types=1);

namespace PhpDeal\Tests\Fixtures\Verify;

use PhpDeal\Attribute\Verify;

/**
 * Contracts of a parent class are evaluated in its own scope, so they can read its private state
 */
abstract class PrivateParent
{
    private int $minimum = 10;

    #[Verify('$value >= $this->minimum')]
    public function set(int $value): void {}
}
