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
use PhpDeal\Contract\Fetcher\ParentClass\InvariantFetcher;
use PhpDeal\Exception\ContractViolation;
use PhpDeal\Annotation\Invariant;
use ReflectionClass;

class InvariantContract extends Contract
{
    /**
     * Verifies invariants for contract class
     *
     * @Around("@within(PhpDeal\Annotation\Invariant) && execution(public **->*(*))")
     * @param MethodInvocation $invocation
     * @throws ContractViolation
     * @return mixed
     */
    public function check(MethodInvocation $invocation)
    {
        $object = $invocation->getThis();
        $args   = $this->getMethodArguments($invocation);
        $class  = $invocation->getMethod()->getDeclaringClass();
        if ($class->isCloneable()) {
            $args['__old'] = clone $object;
        }

        $result = $invocation->proceed();
        $args['__result'] = $result;

        $allContracts = $this->makeContractsUnique($this->fetchAllContracts($class));
        $this->fulfillContracts($allContracts, $object, $class->name, $args, $invocation);

        return $result;
    }

    /**
     * @param ReflectionClass $class
     * @return array
     */
    private function fetchAllContracts(ReflectionClass $class)
    {
        $allContracts = $this->fetchParentsContracts($class);
        foreach ($this->reader->getClassAnnotations($class) as $annotation) {
            if ($annotation instanceof Invariant) {
                $allContracts[] = $annotation;
            }
        }

        return $allContracts;
    }

    /**
     * @param ReflectionClass $class
     * @return array
     */
    private function fetchParentsContracts(ReflectionClass $class)
    {
        return (new InvariantFetcher(Invariant::class))->getConditions(
            $class, $this->reader
        );
    }
} 
