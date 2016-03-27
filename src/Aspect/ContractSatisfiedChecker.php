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
use DomainException;

class ContractSatisfiedChecker
{
    /**
     * Returns a result of contract verification
     *
     * @param object|string $instance Invocation instance or string for static class
     * @param string $scope Scope of method
     * @param array $args List of arguments for the method
     * @param Annotation $annotation Contract annotation
     * @throws DomainException
     */
    public function ensureContractSatisfied($instance, $scope, array $args, $annotation)
    {
        static $invoker = null;
        if (!$invoker) {
            $invoker = function () {
                extract(func_get_arg(0));

                return eval('return ' . func_get_arg(1) . '; ?>');
            };
        }

        $instance = is_object($instance) ? $instance : null;
        $invocationResult = $invoker->bindTo($instance, $scope)->__invoke($args, $annotation->value);

        // we accept as a result only true or null
        // null may be a result of assertions from beberlei/assert which passed
        if ($invocationResult !== null && $invocationResult !== true) {
            $errorMessage = 'Invalid return value received from the assertion body, only boolean or void accepted';
            throw new DomainException($errorMessage);
        }
    }
} 
