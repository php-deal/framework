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
use PhpDeal\Contract\PreconditionContract;
use PhpDeal\Exception\ContractViolation;
use Go\Lang\Annotation\Before;

class PreconditionCheckerAspect implements Aspect
{
    /**
     * @var PreconditionContract
     */
    private $contractChecker;

    public function __construct(Reader $reader)
    {
        $this->contractChecker = new PreconditionContract($reader);
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
        $this->contractChecker->check($invocation);
    }
}
