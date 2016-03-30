<?php
/**
 * PHP Deal framework
 *
 * @copyright Copyright 2014, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpDeal\Contract;

use Go\Aop\Intercept\MethodInvocation;
use PhpDeal\Contract\Fetcher\ParentClass\MethodConditionWithInheritDocFetcher;
use PhpDeal\Exception\ContractViolation;
use PhpDeal\Annotation\Verify;

class PreconditionContract extends Contract
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

        $allContracts = $this->makeContractsUnique($this->fetchAllContracts($invocation));
        $this->fulfillContracts($allContracts, $object, $scope, $args, $invocation);
    }

    /**
     * @param MethodInvocation $invocation
     * @return array
     */
    private function fetchAllContracts(MethodInvocation $invocation)
    {
        $allContracts = $this->fetchParentsContracts($invocation);

        foreach ($invocation->getMethod()->getAnnotations() as $annotation) {
            if ($annotation instanceof Verify) {
                $allContracts[] = $annotation;
            }
        }

        return $allContracts;
    }

    /**
     * @param MethodInvocation $invocation
     * @return array
     */
    private function fetchParentsContracts(MethodInvocation $invocation)
    {
        return (new MethodConditionWithInheritDocFetcher(Verify::class))->getConditions(
            $invocation->getMethod()->getDeclaringClass(),
            $this->reader,
            $invocation->getMethod()->getName()
        );
    }
} 
