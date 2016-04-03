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
use PhpDeal\Annotation\Invariant;
use PhpDeal\Contract\Fetcher\ParentClass\InvariantFetcher;
use PhpDeal\Exception\ContractViolation;
use Go\Lang\Annotation\Around;
use ReflectionClass;

class InvariantCheckerAspect extends AbstractContractAspect implements Aspect
{
    /**
     * @var InvariantFetcher
     */
    private $invariantFetcher;

    public function __construct(Reader $reader)
    {
        parent::__construct($reader);
        $this->invariantFetcher = new InvariantFetcher(Invariant::class, $reader);
    }

    /**
     * Verifies invariants for contract class
     *
     * @Around("@within(PhpDeal\Annotation\Invariant) && execution(public **->*(*))")
     * @param MethodInvocation $invocation
     *
     * @throws ContractViolation
     * @return mixed
     */
    public function invariantContract(MethodInvocation $invocation)
    {
        $object = $invocation->getThis();
        $args   = $this->fetchMethodArguments($invocation);
        $class  = $invocation->getMethod()->getDeclaringClass();
        if ($class->isCloneable()) {
            $args['__old'] = clone $object;
        }

        $result = $invocation->proceed();
        $args['__result'] = $result;

        $allContracts = $this->fetchAllContracts($class);
        $this->ensureContracts($invocation, $allContracts, $object, $class->name, $args);

        return $result;
    }

    /**
     * @param ReflectionClass $class
     * @return array
     */
    private function fetchAllContracts(ReflectionClass $class)
    {
        $allContracts = $this->invariantFetcher->getConditions($class);
        foreach ($this->reader->getClassAnnotations($class) as $annotation) {
            if ($annotation instanceof Invariant) {
                $allContracts[] = $annotation;
            }
        }

        return array_unique($allContracts);
    }
}
