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
use Doctrine\Common\Annotations\Reader;
use Go\Aop\Aspect;
use Go\Aop\Intercept\MethodInvocation;
use Go\Lang\Annotation\Around;
use Go\Lang\Annotation\Before;
use PhpDeal\Annotation as Contract;
use PhpDeal\Exception\ContractViolation;
use DomainException;

/**
 */
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
            } catch (DomainException $e) {
                throw new ContractViolation($invocation, $annotation->value, $e->getPrevious());
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
        $object = $invocation->getThis();
        $args   = $this->getMethodArguments($invocation);
        $class  = $invocation->getMethod()->getDeclaringClass();
        if ($class->isCloneable()) {
            $args['__old'] = clone $object;
        }

        $result = $invocation->proceed();
        $args['__result'] = $result;

        foreach ($invocation->getMethod()->getAnnotations() as $annotation) {
            if (!$annotation instanceof Contract\Ensure) {
                continue;
            }

            try {
                $this->ensureContractSatisfied($object, $class->name, $args, $annotation);
            } catch (DomainException $e) {
                throw new ContractViolation($invocation, $annotation->value, $e->getPrevious());
            }
        }

        return $result;
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
            } catch (DomainException $e) {
                throw new ContractViolation($invocation, $annotation->value, $e->getPrevious());
            }
        }

        return $result;
    }

    /**
     * Returns a result of contract verification
     *
     * @param object|string $instance Invocation instance or string for static class
     * @param string $scope Scope of method
     * @param array $args List of arguments for the method
     * @param Annotation $annotation Contract annotation
     * @throws DomainException
     */
    private function ensureContractSatisfied($instance, $scope, array $args, $annotation)
    {
        static $invoker = null;
        if (!$invoker) {
            $invoker = function () {
                extract(func_get_arg(0));

                return eval('return ' . func_get_arg(1) . '; ?>');
            };
        }
        $instance = is_object($instance) ? $instance : null;

        try {
            $invocationResult = $invoker->bindTo($instance, $scope)->__invoke($args, $annotation->value);
        } catch (\Exception $e) {
            throw new DomainException("", 0, $e);
        }

        // we accept as a result only true or null
        // null may be a result of assertions from beberlei/assert which passed
        if ($invocationResult !== null && $invocationResult !== true) {
            throw new DomainException();
        }
    }

    /**
     * Returns an associative list of arguments for the method invocation
     *
     * @param MethodInvocation $invocation
     *
     * @return array
     */
    private function getMethodArguments(MethodInvocation $invocation)
    {
        $parameters    = $invocation->getMethod()->getParameters();
        $argumentNames = array_map(function (\ReflectionParameter $parameter) {
            return $parameter->name;
        }, $parameters);
        $parameters    = array_combine($argumentNames, $invocation->getArguments());

        return $parameters;
    }
}
