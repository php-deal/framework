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
use PhpDeal\Contract\PostconditionContract;
use PhpDeal\Exception\ContractViolation;
use Go\Lang\Annotation\Around;

class PostconditionCheckerAspect implements Aspect
{
    /**
     * @var PostconditionContract
     */
    private $contractChecker;

    public function __construct(Reader $reader)
    {
        $this->contractChecker = new PostconditionContract($reader);
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
        return $this->contractChecker->check($invocation);
    }
}
