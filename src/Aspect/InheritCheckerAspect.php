<?php
declare(strict_types=1);

namespace PhpDeal\Aspect;

use Doctrine\Common\Annotations\Reader;
use Go\Aop\Aspect;
use Go\Aop\Intercept\MethodInvocation;
use Go\Lang\Annotation\Around;
use PhpDeal\Annotation\Ensure;
use PhpDeal\Annotation\Invariant;
use PhpDeal\Annotation\Verify;
use PhpDeal\Contract\Fetcher\Parent\InvariantFetcher;
use PhpDeal\Contract\Fetcher\Parent\MethodConditionWithInheritDocFetcher;
use PhpDeal\Exception\ContractViolation;
use ReflectionClass;

class InheritCheckerAspect extends AbstractContractAspect implements Aspect
{
    /**
     * @var MethodConditionWithInheritDocFetcher
     */
    private $methodConditionFetcher;

    /** @var InvariantFetcher */
    private $invariantFetcher;

    public function __construct(Reader $reader)
    {
        parent::__construct($reader);
        $this->methodConditionFetcher = new MethodConditionWithInheritDocFetcher([Ensure::class, Verify::class, Invariant::class], $reader);
        $this->invariantFetcher = new InvariantFetcher([Invariant::class], $reader);
    }

    /**
     * Verifies inherit contracts for the method
     *
     * @Around("@execution(PhpDeal\Annotation\Inherit)")
     * @param MethodInvocation $invocation
     *
     * @throws ContractViolation
     * @return mixed
     */
    public function inheritMethodContracts(MethodInvocation $invocation)
    {
        $object = $invocation->getThis();
        $args   = $this->fetchMethodArguments($invocation);
        $class  = $invocation->getMethod()->getDeclaringClass();
        if ($class->isCloneable()) {
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
     */
    public function inheritClassContracts(MethodInvocation $invocation)
    {
        $object = $invocation->getThis();
        $args   = $this->fetchMethodArguments($invocation);
        $class  = $invocation->getMethod()->getDeclaringClass();
        if ($class->isCloneable()) {
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
     */
    private function fetchMethodContracts(MethodInvocation $invocation)
    {
        $allContracts = $this->fetchParentsMethodContracts($invocation);

        foreach ($invocation->getMethod()->getAnnotations() as $annotation) {
            $annotationClass = \get_class($annotation);

            if (\in_array($annotationClass, [Ensure::class, Verify::class, Invariant::class], true)) {
                $allContracts[] = $annotation;
            }
        }

        return array_unique($allContracts);
    }

    /**
     * @param MethodInvocation $invocation
     * @return array
     */
    private function fetchParentsMethodContracts(MethodInvocation $invocation)
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
    private function fetchClassContracts(ReflectionClass $class)
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
