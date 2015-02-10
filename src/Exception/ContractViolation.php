<?php
/**
 * PHP Deal framework
 *
 * @copyright Copyright 2014, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpDeal\Exception;

use Exception;
use Go\Aop\Intercept\MethodInvocation;

/**
 * Specific contract violation exception to point to the file and line of invocation
 */
class ContractViolation extends \LogicException
{

    /**
     * Violation constructor
     *
     * @param MethodInvocation $invocation Current method invocation
     * @param string $contract Violated contract code
     * @param Exception $previous
     */
    public function __construct(MethodInvocation $invocation, $contract, Exception $previous = null)
    {
        $obj        = $invocation->getThis();
        $objName    = is_object($obj) ? get_class($obj) : $obj;
        $method     = $invocation->getMethod();

        $message = "Contract {$contract} violated for {$objName}->{$method->name}";
        parent::__construct($message, 0, $previous);

        $this->file = $method->getFileName();
        $this->line = $method->getStartLine() + 1;
    }

}
