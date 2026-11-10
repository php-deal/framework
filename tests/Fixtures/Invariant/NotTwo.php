<?php

declare(strict_types=1);

namespace PhpDeal\Tests\Fixtures\Invariant;

use PhpDeal\Attribute\Invariant;

#[Invariant('$this->value !== 2')]
interface NotTwo {}
