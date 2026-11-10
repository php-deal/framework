<?php

declare(strict_types=1);

namespace PhpDeal\Tests\Fixtures\Invariant;

use PhpDeal\Attribute\Invariant;

#[Invariant('$this->value !== 3')]
interface NotThree {}
