<?php
/**
 * PHP Deal framework
 *
 * @copyright Copyright 2014, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpDeal\Aspect;

use Doctrine\Common\Annotations\Reader;
use Go\Aop\Aspect;
use Go\Aop\Intercept\MethodInvocation;
use PhpDeal\Annotation\Verify;
use PhpDeal\Contract\Fetcher\ParentClass\MethodConditionWithInheritDocFetcher;
use PhpDeal\Exception\ContractViolation;
use Go\Lang\Annotation\Before;

class PreconditionCheckerAspect extends AbstractContractAspect implements Aspect
{
    /**
     * @var MethodConditionWithInheritDocFetcher
     */
    private $methodConditionFetcher;

    public function __construct(Reader $reader)
    {
        parent::__construct($reader);
        $this->methodConditionFetcher = new MethodConditionWithInheritDocFetcher(Verify::class, $reader);
    }

    /**
     * Verifies pre-condition contract for the method
     *
     * @param MethodInvocation $invocation
     * @Before("@execution(PhpDeal\Annotation\Verify)")
     *
     * @throws ContractViolation
     */
    public function preConditionContract(MethodInvocation $invocation)
    {
        $object = $invocation->getThis();
        $args   = $this->fetchMethodArguments($invocation);
        $scope  = $invocation->getMethod()->getDeclaringClass()->name;

        $allContracts = $this->fetchAllContracts($invocation);
        $this->ensureContracts($invocation, $allContracts, $object, $scope, $args);
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

        return array_unique($allContracts);
    }

    /**
     * @param MethodInvocation $invocation
     * @return array
     */
    private function fetchParentsContracts(MethodInvocation $invocation)
    {
        return $this->methodConditionFetcher->getConditions(
            $invocation->getMethod()->getDeclaringClass(),
            $invocation->getMethod()->name
        );
    }
}
