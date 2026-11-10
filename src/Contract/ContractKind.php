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

namespace PhpDeal\Contract;

use Go\Aop\Intercept\MethodInvocation;
use PhpDeal\Exception\ContractViolation;
use PhpDeal\Exception\InvariantViolation;
use PhpDeal\Exception\PostconditionViolation;
use PhpDeal\Exception\PreconditionViolation;
use Throwable;

/**
 * Kind of a contract check, decides which exception reports a violation
 */
enum ContractKind
{
    case Precondition;
    case Postcondition;
    case Invariant;

    public function createViolation(
        MethodInvocation $invocation,
        string $expression,
        ?Throwable $previous = null,
    ): ContractViolation {
        return match ($this) {
            self::Precondition  => new PreconditionViolation($invocation, $expression, $previous),
            self::Postcondition => new PostconditionViolation($invocation, $expression, $previous),
            self::Invariant     => new InvariantViolation($invocation, $expression, $previous),
        };
    }
}
