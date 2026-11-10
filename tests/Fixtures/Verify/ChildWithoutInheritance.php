<?php

declare(strict_types=1);

namespace PhpDeal\Tests\Fixtures\Verify;

use PhpDeal\Attribute\Verify;

/**
 * Preconditions of the parent are not inherited: this method accepts 2
 */
class ChildWithoutInheritance extends ParentWithoutInheritance
{
    #[Verify('$value !== 1')]
    public function add(int $value): void {}
}
