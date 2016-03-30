<?php
/**
 * PHP Deal framework
 *
 * @copyright Copyright 2014, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpDeal\Contract;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\Reader;
use DomainException;
use Go\Aop\Intercept\MethodInvocation;
use PhpDeal\Contract\Fetcher\MethodArgument;
use PhpDeal\Exception\ContractViolation;

abstract class Contract
{
    /**
     * @var Reader|null
     */
    protected $reader = null;

    /**
     * @param Reader $reader Annotation reader
     */
    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * @param MethodInvocation $invocation
     * @return array
     */
    protected function getMethodArguments(MethodInvocation $invocation)
    {
        return (new MethodArgument())->fetch($invocation);
    }

    /**
     * @param array $allContracts
     * @return array
     */
    protected function makeContractsUnique(array $allContracts)
    {
        return array_unique($allContracts);
    }

    /**
     * @param array $allContracts
     * @param object $instance
     * @param string $scope
     * @param array $args
     * @param MethodInvocation $invocation
     */
    protected function fulfillContracts($allContracts, $instance, $scope, array $args, MethodInvocation $invocation)
    {
        foreach ($allContracts as $contract) {
            try {
                $this->ensureContractSatisfied($instance, $scope, $args, $contract);
            } catch (\Exception $e) {
                throw new ContractViolation($invocation, $contract->value, $e);
            }
        }
    }

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
