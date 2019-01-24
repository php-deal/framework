<?php
declare(strict_types=1);

/**
 * PHP Deal framework
 *
 * @copyright Copyright 2019, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpDeal\Exception;

use Go\Aop\Intercept\MethodInvocation;
use Throwable;

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
     * @param Throwable $previous
     */
    public function __construct(MethodInvocation $invocation, $contract, Throwable $previous = null)
    {
        $obj        = $invocation->getThis();
        $objName    = \is_object($obj) ? \get_class($obj) : $obj;
        $method     = $invocation->getMethod();
        $args       = \implode(', ', $invocation->getArguments());

        $message = "Contract {$contract} violated with argument set {{$args}} for {$objName}->{$method->name}";
        parent::__construct($message, 0, $previous);

        $this->file = $method->getFileName();
        $this->line = $method->getStartLine() + 1;
    }
}
