<?php
/**
 * PHP Deal framework
 *
 * @copyright Copyright 2014, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpDeal\Aspect\ContractChecker;

use Go\Aop\Intercept\MethodInvocation;
use PhpDeal\Exception\ContractViolation;
use PhpDeal\Annotation as Contract;

class PreConditionChecker extends ContractChecker
{
    /**
     * @param MethodInvocation $invocation
     * @Before("@execution(PhpDeal\Annotation\Verify)")
     * @throws ContractViolation
     */
    public function check(MethodInvocation $invocation)
    {
        $object = $invocation->getThis();
        $args   = $this->getMethodArguments($invocation);
        $scope  = $invocation->getMethod()->getDeclaringClass()->name;

        foreach ($invocation->getMethod()->getAnnotations() as $annotation) {
            if (!$annotation instanceof Contract\Verify) {
                continue;
            }

            try {
                $this->ensureContractSatisfied($object, $scope, $args, $annotation);
            } catch (\Exception $e) {
                throw new ContractViolation($invocation, $annotation->value, $e);
            }
        }
    }
} 
