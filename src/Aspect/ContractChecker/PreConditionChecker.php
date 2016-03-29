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
use PhpDeal\Annotation\Verify;
use ReflectionMethod;

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

        $allContracts = $this->makeContractsUnique($this->fetchAllContracts($invocation));
        $this->fulfillContracts($allContracts, $object, $scope, $args, $invocation);
    }

    /**
     * @param MethodInvocation $invocation
     * @return array
     */
    private function fetchAllContracts(MethodInvocation $invocation)
    {
        $allContracts = [];
        if ($this->hasInheritDoc($invocation->getMethod())) {
            $allContracts = $this->fetchParentsContracts($invocation);
        }

        foreach ($invocation->getMethod()->getAnnotations() as $annotation) {
            if ($annotation instanceof Verify) {
                $allContracts[] = $annotation;
            }
        }

        return $allContracts;
    }

    /**
     * @param ReflectionMethod $method
     * @return bool
     */
    private function hasInheritDoc(ReflectionMethod $method)
    {
        return (new InheritDoc())->hasInheritDoc($method);
    }

    /**
     * @param MethodInvocation $invocation
     * @return array
     */
    private function fetchParentsContracts(MethodInvocation $invocation)
    {
        return $this->getParentsContractsWithInheritDoc(
            Verify::class,
            $invocation->getMethod()->getDeclaringClass(),
            $this->reader,
            [],
            $invocation->getMethod()->getName()
        );
    }
} 
