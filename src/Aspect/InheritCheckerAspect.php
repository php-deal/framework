<?php

namespace PhpDeal\Aspect;

use Doctrine\Common\Annotations\Reader;
use Go\Aop\Aspect;
use Go\Aop\Intercept\MethodInvocation;
use Go\Aop\Support\AnnotatedReflectionMethod;
use Go\Lang\Annotation\Around;
use PhpDeal\Annotation\Ensure;
use PhpDeal\Annotation\Invariant;
use PhpDeal\Annotation\Verify;
use PhpDeal\Contract\Fetcher\Parent\InvariantFetcher;
use PhpDeal\Contract\Fetcher\Parent\MethodConditionFetcher;
use PhpDeal\Exception\ContractViolation;
use ReflectionClass;
use ReflectionMethod;

class InheritCheckerAspect extends AbstractContractAspect implements Aspect
{
    /**
     * @var MethodConditionFetcher
     */
    private $methodConditionFetcher;

    /** @var InvariantFetcher */
    private $invariantFetcher;

    public function __construct(Reader $reader)
    {
        parent::__construct($reader);
        $this->methodConditionFetcher = new MethodConditionFetcher([
            Ensure::class,
            Verify::class,
            Invariant::class
        ], $reader);
        $this->invariantFetcher = new InvariantFetcher([Invariant::class], $reader);
    }

    /**
     * Verifies inherit contracts for the method
     *
     * @Around("@execution(PhpDeal\Annotation\Inherit)")
     * @param MethodInvocation $invocation
     *
     * @throws ContractViolation
     * @throws \ReflectionException
     * @return mixed
     */
    public function inheritMethodContracts(MethodInvocation $invocation)
    {
        $object = $invocation->getThis();
        $args   = $this->fetchMethodArguments($invocation);
        $class  = $invocation->getMethod()->getDeclaringClass();
        if (\is_object($object) && $class->isCloneable()) {
            $args['__old'] = clone $object;
        }

        $result = $invocation->proceed();
        $args['__result'] = $result;
        $allContracts = $this->fetchMethodContracts($invocation);

        $this->ensureContracts($invocation, $allContracts, $object, $class->name, $args);

        return $result;
    }

    /**
     * @Around("@within(PhpDeal\Annotation\Inherit) && execution(public **->*(*))")
     * @param MethodInvocation $invocation
     * @return mixed
     * @throws \ReflectionException
     */
    public function inheritClassContracts(MethodInvocation $invocation)
    {
        $object = $invocation->getThis();
        $args   = $this->fetchMethodArguments($invocation);
        $class  = $invocation->getMethod()->getDeclaringClass();
        if (\is_object($object) && $class->isCloneable()) {
            $args['__old'] = clone $object;
        }

        $result = $invocation->proceed();
        $args['__result'] = $result;

        $allContracts = $this->fetchClassContracts($class);
        $this->ensureContracts($invocation, $allContracts, $object, $class->name, $args);

        return $result;
    }

    /**
     * @param MethodInvocation $invocation
     * @return array
     * @throws \ReflectionException
     */
    private function fetchMethodContracts(MethodInvocation $invocation): array
    {
        $allContracts = $this->fetchParentsMethodContracts($invocation);
        /** @var ReflectionMethod&AnnotatedReflectionMethod $reflectionMethod */
        $reflectionMethod = $invocation->getMethod();

        foreach ($reflectionMethod->getAnnotations() as $annotation) {
            $annotationClass = \get_class($annotation);

            if (\in_array($annotationClass, [Ensure::class, Verify::class, Invariant::class], true)) {
                $allContracts[] = $annotation;
            }
        }

        return \array_unique($allContracts);
    }

    /**
     * @param MethodInvocation $invocation
     * @return array
     * @throws \ReflectionException
     */
    private function fetchParentsMethodContracts(MethodInvocation $invocation): array
    {
        return $this->methodConditionFetcher->getConditions(
            $invocation->getMethod()->getDeclaringClass(),
            $invocation->getMethod()->name
        );
    }

    /**
     * @param ReflectionClass $class
     * @return array
     */
    private function fetchClassContracts(ReflectionClass $class): array
    {
        $allContracts = $this->invariantFetcher->getConditions($class);
        foreach ($this->reader->getClassAnnotations($class) as $annotation) {
            if ($annotation instanceof Invariant) {
                $allContracts[] = $annotation;
            }
        }

        return \array_unique($allContracts);
    }
}
