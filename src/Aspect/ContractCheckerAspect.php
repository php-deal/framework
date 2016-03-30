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
use Go\Lang\Annotation\Around;
use Go\Lang\Annotation\Before;
use PhpDeal\Annotation as Contract;
use PhpDeal\Contract\InvariantContract;
use PhpDeal\Contract\PostconditionContract;
use PhpDeal\Contract\PreconditionContract;
use PhpDeal\Exception\ContractViolation;

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
        (new PreconditionContract($this->reader))->check($invocation);
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
        return (new PostconditionContract($this->reader))->check($invocation);
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
        return (new InvariantContract($this->reader))->check($invocation);
    }
}
