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

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Cache\ArrayCache;
use Go\Aop\Intercept\MethodInvocation;
use PhpDeal\Exception\ContractViolation;
use PhpDeal\Annotation\Ensure;

class PostConditionChecker extends ContractChecker
{
    /**
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
        $allContracts = $this->makeContractsUnique($this->fetchAllContracts($invocation));

        foreach ($allContracts as $contract) {
            try {
                $this->ensureContractSatisfied($object, $class->name, $args, $contract);
            } catch (\Exception $e) {
                throw new ContractViolation($invocation, $contract->value, $e);
            }
        }

        return $result;
    }

    /**
     * @param MethodInvocation $invocation
     * @return array
     */
    private function fetchAllContracts(MethodInvocation $invocation)
    {
        $allContracts = $this->fetchParentsContracts($invocation);
        foreach ($invocation->getMethod()->getAnnotations() as $annotation) {
            if ($annotation instanceof Ensure) {
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
        return $this->getParentsContracts(
            Ensure::class,
            $invocation->getMethod()->getDeclaringClass(),
            $this->reader,
            [],
            $invocation->getMethod()->getName()
        );
    }

    /**
     * @param array $allContracts
     * @return array
     */
    private function makeContractsUnique(array $allContracts)
    {
        return array_unique($allContracts);
    }
}
