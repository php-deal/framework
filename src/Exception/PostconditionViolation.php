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
 * Thrown when a post-condition (#[Ensure]) does not hold: the method did not keep its promise
 */
final class PostconditionViolation extends ContractViolation
{
    protected const string KIND = 'Postcondition';
}
