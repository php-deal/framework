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

namespace PhpDeal\Aspect;

use Go\Aop\Aspect;
use Go\Aop\Intercept\MethodInvocation;
use Go\Lang\Attribute\Around;
use PhpDeal\Contract\ContractEvaluator;
use PhpDeal\Contract\ContractFetcher;
use PhpDeal\Contract\ContractKind;
use PhpDeal\Exception\InvariantViolation;

/**
 * Checks `#[Invariant]` class invariants after the constructor and after every public method
 *
 * The invariants of the object's own class, its parent classes and its interfaces are checked.
 */
final readonly class InvariantCheckerAspect implements Aspect
{
    public function __construct(
        private ContractFetcher $fetcher,
        private ContractEvaluator $evaluator,
    ) {}

    /**
     * @throws InvariantViolation
     */
    #[Around(
        '(@within(PhpDeal\Attribute\Invariant) || @within(PhpDeal\Attribute\Inherit))'
        . ' && execution(public **->*(*))',
    )]
    public function checkInvariants(MethodInvocation $invocation): mixed
    {
        $result   = $invocation->proceed();
        $instance = $invocation->getThis();
        if ($instance === null || $this->evaluator->isEvaluating()) {
            return $result;
        }
        $contracts = $this->fetcher->getInvariants($instance::class);
        if ($contracts === []) {
            return $result;
        }

        $variables             = $this->evaluator->getArguments($invocation);
        $variables['__result'] = $result;
        $this->evaluator->check($invocation, $contracts, $variables, ContractKind::Invariant);

        return $result;
    }
}
