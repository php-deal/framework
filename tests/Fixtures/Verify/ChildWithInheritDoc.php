<?php

declare(strict_types=1);

namespace PhpDeal\Tests\Fixtures\Verify;

use PhpDeal\Attribute\Verify;

class ChildWithInheritDoc extends ParentWithInheritDoc
{
    /**
     * {@inheritdoc}
     */
    #[Verify('$value !== 1')]
    public function add(int $value): void {}
}
