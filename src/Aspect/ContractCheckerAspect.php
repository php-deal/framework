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

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Cache\ArrayCache;
use Go\Aop\Aspect;
use Go\Aop\Intercept\MethodInvocation;
use Go\Lang\Annotation\Around;
use Go\Lang\Annotation\Before;
use PhpDeal\Annotation as Contract;
use PhpDeal\Exception\ContractViolation;
use DomainException;
use ReflectionClass;
use PhpDeal\Aspect\ContractChecker\PostConditionChecker;

class ContractCheckerAspect implements Aspect
{

    /**
     * Annotation reader
     *
     * @var Reader|null
     */
    private $reader = null;

    /**
     * Default constructor
     *
     * @param Reader $reader Annotation reader
     */
    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
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

    /**
     * Verifies post-condition contract for the method
     *
     * @Around("@execution(PhpDeal\Annotation\Ensure)")
     * @param MethodInvocation $invocation
     *
     * @throws ContractViolation
     * @return mixed
     */
    public function postConditionContract(MethodInvocation $invocation)
    {
        return (new PostConditionChecker())->conditionContract($invocation);
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
        $args   = $this->getMethodArguments($invocation);
        $class  = $invocation->getMethod()->getDeclaringClass();
        if ($class->isCloneable()) {
            $args['__old'] = clone $object;
        }

        $result = $invocation->proceed();
        $args['__result'] = $result;

        foreach ($this->reader->getClassAnnotations($class) as $annotation) {
            if (!$annotation instanceof Contract\Invariant) {
                continue;
            }

            try {
                $this->ensureContractSatisfied($object, $class->name, $args, $annotation);
            } catch (\Exception $e) {
                throw new ContractViolation($invocation, $annotation->value, $e);
            }
        }

        return $result;
    }
}
