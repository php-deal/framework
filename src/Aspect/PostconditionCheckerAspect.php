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
use PhpDeal\Contract\BoundContract;
use PhpDeal\Contract\ContractEvaluator;
use PhpDeal\Contract\ContractFetcher;
use PhpDeal\Contract\ContractKind;
use PhpDeal\Exception\PostconditionViolation;

/**
 * Checks `#[Ensure]` post-conditions after the method body has returned
 *
 * Expressions can use `$__result` (the return value) and `$__old` (a clone of the object taken before the call).
 */
final readonly class PostconditionCheckerAspect implements Aspect
{
    public function __construct(
        private ContractFetcher $fetcher,
        private ContractEvaluator $evaluator,
    ) {}

    /**
     * @throws PostconditionViolation
     */
    #[Around(
        '@execution(PhpDeal\Attribute\Ensure)'
        . ' || @execution(PhpDeal\Attribute\Inherit)'
        . ' || (@within(PhpDeal\Attribute\Inherit) && (execution(public **->*(*)) || execution(public **::*(*))))',
    )]
    public function checkPostconditions(MethodInvocation $invocation): mixed
    {
        if ($this->evaluator->isEvaluating()) {
            return $invocation->proceed();
        }
        $contracts = $this->fetcher->getPostconditions($invocation->getMethod());
        if ($contracts === []) {
            return $invocation->proceed();
        }

        $variables = $this->evaluator->getArguments($invocation);
        $instance  = $invocation->getThis();
        // Cloning is skipped when no contract needs the previous state
        if ($instance !== null && self::usesOldState($contracts)) {
            $oldState = $this->evaluator->cloneState($instance);
            if ($oldState !== null) {
                $variables['__old'] = $oldState;
            }
        }

        $result                = $invocation->proceed();
        $variables['__result'] = $result;
        $this->evaluator->check($invocation, $contracts, $variables, ContractKind::Postcondition);

        return $result;
    }

    /**
     * @param list<BoundContract> $contracts
     */
    private static function usesOldState(array $contracts): bool
    {
        foreach ($contracts as $boundContract) {
            if ($boundContract->usesOldState) {
                return true;
            }
        }

        return false;
    }
}
