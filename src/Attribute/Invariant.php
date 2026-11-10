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
use Closure;

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
     * @param string       $expression PHP expression that must evaluate to true
     * @param Closure|null $check      The expression compiled into a static closure. PhpDeal writes it into the woven
     *                                 class on PHP 8.5+, it is not meant to be written by hand
     */
    public function __construct(
        public string $expression,
        public ?Closure $check = null,
    ) {}
}
