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

namespace PhpDeal\Exception;

/**
 * Thrown when a class invariant (#[Invariant]) does not hold after a public method call
 */
final class InvariantViolation extends ContractViolation
{
    protected const string KIND = 'Invariant';
}
