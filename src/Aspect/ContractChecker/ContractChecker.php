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

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\Reader;
use DomainException;
use Go\Aop\Intercept\MethodInvocation;
use PhpDeal\Aspect\Fetcher\MethodArgumentsFetcher;
use PhpDeal\Aspect\Fetcher\ParentsContractsFetcher;
use ReflectionClass;

abstract class ContractChecker
{
    /**
     * @param MethodInvocation $invocation
     * @return array
     */
    protected function getMethodArguments(MethodInvocation $invocation)
    {
        return (new MethodArgumentsFetcher())->fetch($invocation);
    }

    /**
     * @param object|string $instance Invocation instance or string for static class
     * @param string $scope Scope of method
     * @param array $args List of arguments for the method
     * @param Annotation $annotation Contract annotation
     * @throws DomainException
     */
    protected function ensureContractSatisfied($instance, $scope, array $args, $annotation)
    {
        (new SatisfiedContract())->ensureContractSatisfied(
            $instance, $scope, $args, $annotation
        );
    }

    protected function getParentsContracts($annotationClass, ReflectionClass $class, Reader $reader, array $contracts, $methodName)
    {
        return (new ParentsContractsFetcher($annotationClass))->getParentsContracts(
            $class, $reader, $contracts, $methodName
        );
    }
}
