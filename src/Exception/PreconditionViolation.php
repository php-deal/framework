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
 * Thrown when a pre-condition (#[Verify]) does not hold: the caller passed invalid arguments
 */
final class PreconditionViolation extends ContractViolation
{
    protected const string KIND = 'Precondition';
}
