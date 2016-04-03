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
use Doctrine\Common\Annotations\Reader;
use DomainException;
use Go\Aop\Intercept\MethodInvocation;
use PhpDeal\Exception\ContractViolation;

abstract class AbstractContractAspect
{
    /**
     * @var Reader
     */
    protected $reader;

    /**
     * @param Reader $reader Annotation reader
     */
    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * Returns an associative list of arguments for the method invocation
     *
     * @param MethodInvocation $invocation
     * @return array
     */
    protected function fetchMethodArguments(MethodInvocation $invocation)
    {
        $parameters = $invocation->getMethod()->getParameters();
        $argumentNames = array_map(function (\ReflectionParameter $parameter) {
            return $parameter->name;
        }, $parameters);
        $parameters = array_combine($argumentNames, $invocation->getArguments());

        return $parameters;
    }

    /**
     * Performs verification of contracts for given invocation
     *
     * @param MethodInvocation $invocation Current invocation
     * @param array|Annotation[] $contracts Contract annotation
     * @param object|string $instance Invocation instance or string for static class
     * @param string $scope Scope of method
     * @param array $args List of arguments for the method
     *
     * @throws DomainException
     */
    protected function ensureContracts(MethodInvocation $invocation, array $contracts, $instance, $scope, array $args)
    {
        static $invoker = null;
        if (!$invoker) {
            $invoker = function () {
                extract(func_get_arg(0));

                return eval('return ' . func_get_arg(1) . '; ?>');
            };
        }

        $instance     = is_object($instance) ? $instance : null;
        $boundInvoker = $invoker->bindTo($instance, $scope);

        foreach ($contracts as $contract) {
            $contractExpression = $contract->value;
            try {
                $invocationResult = $boundInvoker->__invoke($args, $contractExpression);

                // we accept as a result only true or null
                // null may be a result of assertions from beberlei/assert which passed
                if ($invocationResult !== null && $invocationResult !== true) {
                    $errorMessage = 'Invalid return value received from the assertion body,'
                        . ' only boolean or void can be returned';
                    throw new DomainException($errorMessage);
                }

            } catch (\Error $internalError) {
                // PHP-7 friendly interceptor for fatal errors
                throw new ContractViolation($invocation, $contractExpression, $internalError);
            } catch (\Exception $internalException) {
                throw new ContractViolation($invocation, $contractExpression, $internalException);
            }
        }
    }
}
