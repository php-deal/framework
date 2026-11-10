<?php

declare(strict_types=1);

namespace PhpDeal\Tests\Unit\Stub;

use Attribute;
use PhpDeal\Attribute\Contract;

/**
 * A contract attribute of another package: PhpDeal must never evaluate its expression
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
final readonly class CustomContract implements Contract
{
    public string $expression;

    public function __construct()
    {
        $this->expression = (string) \getenv('PHP_DEAL_CUSTOM_CONTRACT');
    }
}
