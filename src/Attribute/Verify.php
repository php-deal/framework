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
 * Pre-condition: a requirement upon the caller, checked before the method body runs.
 *
 * Arguments of the method are available by their names: `#[Verify('$amount > 0')]`
 *
 * The attribute is repeatable, every expression must hold.
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final readonly class Verify implements Contract
{
    /**
     * @param string $expression PHP expression that must evaluate to true
     */
    public function __construct(public string $expression) {}
}
