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
use Go\Lang\Attribute\Before;
use PhpDeal\Contract\ContractEvaluator;
use PhpDeal\Contract\ContractFetcher;
use PhpDeal\Contract\ContractKind;
use PhpDeal\Exception\PreconditionViolation;

/**
 * Checks `#[Verify]` pre-conditions before the method body runs
 */
final readonly class PreconditionCheckerAspect implements Aspect
{
    public function __construct(
        private ContractFetcher $fetcher,
        private ContractEvaluator $evaluator,
    ) {}

    /**
     * @throws PreconditionViolation
     */
    #[Before(
        '@execution(PhpDeal\Attribute\Verify)'
        . ' || @execution(PhpDeal\Attribute\Inherit)'
        . ' || (@within(PhpDeal\Attribute\Inherit) && (execution(public **->*(*)) || execution(public **::*(*))))',
    )]
    public function checkPreconditions(MethodInvocation $invocation): void
    {
        if ($this->evaluator->isEvaluating()) {
            return;
        }
        $contracts = $this->fetcher->getPreconditions($invocation->getMethod());
        if ($contracts === []) {
            return;
        }

        $variables = $this->evaluator->getArguments($invocation);
        $this->evaluator->check($invocation, $contracts, $variables, ContractKind::Precondition);
    }
}
