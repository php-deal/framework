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
     * @var MethodInvocation
     */
    private $invocation;

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
        $this->invocation = $invocation;
        $object = $invocation->getThis();
        $args   = $this->getMethodArguments($invocation);
        $scope  = $invocation->getMethod()->getDeclaringClass()->name;

        foreach ($invocation->getMethod()->getAnnotations() as $annotation) {
            if (!$annotation instanceof Contract\Verify) {
                continue;
            }

            if (!$this->isContractSatisfied($object, $scope, $args, $annotation)) {
                throw new ContractViolation($invocation, $annotation->value);
            };
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
        $this->invocation = $invocation;
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

            if (!$this->isContractSatisfied($object, $class->name, $args, $annotation)) {
                throw new ContractViolation($invocation, $annotation->value);
            };
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
        $this->invocation = $invocation;
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

            if (!$this->isContractSatisfied($object, $class->name, $args, $annotation)) {
                throw new ContractViolation($invocation, $annotation->value);
            };
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
     *
     * @return mixed
     */
    private function isContractSatisfied($instance, $scope, array $args, $annotation)
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
            throw new ContractViolation($this->invocation, $annotation->value . " Details: " . $e->getMessage());
        }

        // if $invocationResult is null, $annotation->value didn't throw any exception
        // for example - assertion passed (and didn't return bool value)
        return $invocationResult === null || $invocationResult === true;
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
