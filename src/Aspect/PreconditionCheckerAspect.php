<?php

declare(strict_types=1);

/**
 * PHP Deal framework
 *
 * @copyright Copyright 2019, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpDeal\Aspect;

use Doctrine\Common\Annotations\Reader;
use Go\Aop\Aspect;
use Go\Aop\Intercept\MethodInvocation;
use Go\Aop\Support\AnnotatedReflectionMethod;
use PhpDeal\Annotation\Verify;
use PhpDeal\Contract\Fetcher\Parent\MethodConditionWithInheritDocFetcher;
use PhpDeal\Exception\ContractViolation;
use Go\Lang\Annotation\Before;
use ReflectionException;
use ReflectionMethod;

class PreconditionCheckerAspect extends AbstractContractAspect implements Aspect
{
    /**
     * @var MethodConditionWithInheritDocFetcher
     */
    private $methodConditionFetcher;

    public function __construct(Reader $reader)
    {
        parent::__construct($reader);
        $this->methodConditionFetcher = new MethodConditionWithInheritDocFetcher([Verify::class], $reader);
    }

    /**
     * Verifies pre-condition contract for the method
     *
     * @param MethodInvocation $invocation
     * @Before("@execution(PhpDeal\Annotation\Verify)")
     *
     * @throws ContractViolation
     * @throws ReflectionException
     */
    public function preConditionContract(MethodInvocation $invocation): void
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
     * @throws ReflectionException
     */
    private function fetchAllContracts(MethodInvocation $invocation): array
    {
        $allContracts = $this->fetchParentsContracts($invocation);
        /** @var ReflectionMethod&AnnotatedReflectionMethod $reflectionMethod */
        $reflectionMethod = $invocation->getMethod();

        foreach ($reflectionMethod->getAnnotations() as $annotation) {
            if ($annotation instanceof Verify) {
                $allContracts[] = $annotation;
            }
        }

        return \array_unique($allContracts);
    }

    /**
     * @param MethodInvocation $invocation
     * @return array
     * @throws ReflectionException
     */
    private function fetchParentsContracts(MethodInvocation $invocation): array
    {
        return $this->methodConditionFetcher->getConditions(
            $invocation->getMethod()->getDeclaringClass(),
            $invocation->getMethod()->name
        );
    }
}
