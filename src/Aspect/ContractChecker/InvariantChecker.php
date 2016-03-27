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

use Doctrine\Common\Annotations\Reader;
use Go\Aop\Intercept\MethodInvocation;
use PhpDeal\Exception\ContractViolation;
use PhpDeal\Annotation\Invariant;

class InvariantChecker extends ContractChecker
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

        foreach ($this->reader->getClassAnnotations($class) as $annotation) {
            if (!$annotation instanceof Invariant) {
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
