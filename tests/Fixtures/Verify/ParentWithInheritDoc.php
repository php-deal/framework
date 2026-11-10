<?php

declare(strict_types=1);

namespace PhpDeal\Tests\Fixtures\Verify;

use PhpDeal\Attribute\Verify;

abstract class ParentWithInheritDoc extends Grandparent
{
    /**
     * @inheritDoc
     */
    #[Verify('$value !== 2')]
    public function add(int $value): void {}
}
