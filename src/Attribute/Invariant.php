<?php

/**
 * PHP Deal framework
 *
 * @copyright Copyright 2014-2026, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace PhpDeal\Attribute;

use Attribute;

/**
 * Class invariant: a condition that holds after the constructor and after every public method call.
 *
 * Invariants are inherited from parent classes and interfaces: `#[Invariant('$this->balance >= 0')]`
 *
 * The attribute is repeatable, every expression must hold.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class Invariant implements Contract
{
    /**
     * @param string $expression PHP expression that must evaluate to true
     */
    public function __construct(public string $expression) {}
}
