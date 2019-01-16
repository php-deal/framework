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
     * @throws \ReflectionException
     */
    protected function fetchMethodArguments(MethodInvocation $invocation): array
    {
        $result         = [];
        $parameters     = $invocation->getMethod()->getParameters();
        $argumentValues = $invocation->getArguments();

        // Number of arguments can be less than number of parameters because of default values
        foreach ($parameters as $parameterIndex => $reflectionParameter) {
            $hasArgumentValue = \array_key_exists($parameterIndex, $argumentValues);
            $argumentValue    = $hasArgumentValue ? $argumentValues[$parameterIndex] : null;
            if (!$hasArgumentValue && $reflectionParameter->isDefaultValueAvailable()) {
                $argumentValue = $reflectionParameter->getDefaultValue();
            }
            $result[$reflectionParameter->name] = $argumentValue;
        }

        return $result;
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
    protected function ensureContracts(
        MethodInvocation $invocation,
        array $contracts,
        $instance,
        string $scope,
        array $args
    ): void {
        static $invoker = null;
        if (!$invoker) {
            $invoker = function () {
                $args = \func_get_arg(0);
                \extract($args, EXTR_OVERWRITE);

                return eval('return ' . \func_get_arg(1) . '; ?>');
            };
        }

        $instance     = \is_object($instance) ? $instance : null;
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
