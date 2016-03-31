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
use PhpDeal\Contract\InvariantContract;
use PhpDeal\Exception\ContractViolation;
use Go\Lang\Annotation\Around;

class InvariantCheckerAspect implements Aspect
{
    /**
     * @var InvariantContract
     */
    private $contractChecker;

    public function __construct(Reader $reader)
    {
        $this->contractChecker = new InvariantContract($reader);
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
        return $this->contractChecker->check($invocation);
    }
}
