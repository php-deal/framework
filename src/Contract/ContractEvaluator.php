<?php

/**
 * PHP Deal framework
 *
 * @copyright Copyright 2014-2026, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace PhpDeal\Contract;

use Closure;
use Go\Aop\Intercept\MethodInvocation;
use ParseError;
use PhpDeal\Exception\ContractViolation;
use PhpDeal\Exception\InvalidContractException;
use Throwable;

/**
 * Evaluates contracts for a method invocation
 *
 * Contracts compiled while the class was woven (PHP 8.5+, see {@see ContractRewriter}) are called directly. Other
 * expressions are compiled with eval() into closures, once per process. While a contract is evaluated, contracts of nested calls are not
 * checked, so an invariant may call public methods of its own class without recursing endlessly.
 */
final class ContractEvaluator
{
    /**
     * @var array<string, Closure(array<string, mixed>): mixed>
     */
    private array $compiled = [];

    private int $depth = 0;

    /**
     * Tells whether a contract is being evaluated right now
     */
    public function isEvaluating(): bool
    {
        return $this->depth > 0;
    }

    /**
     * Returns the arguments of the invocation keyed by parameter name, with default values filled in
     *
     * @return array<string, mixed>
     */
    public function getArguments(MethodInvocation $invocation): array
    {
        $values    = $invocation->getArguments();
        $variables = [];
        foreach ($invocation->getMethod()->getParameters() as $index => $parameter) {
            if ($parameter->isVariadic()) {
                $variables[$parameter->name] = \array_slice($values, $index);
                break;
            }
            $variables[$parameter->name] = match (true) {
                \array_key_exists($index, $values)    => $values[$index],
                $parameter->isDefaultValueAvailable() => $parameter->getDefaultValue(),
                default                               => null,
            };
        }

        return $variables;
    }

    /**
     * Evaluates every contract and throws on the first one that does not hold
     *
     * @param list<BoundContract>  $contracts Contracts to check
     * @param array<string, mixed> $variables Variables available to the expressions
     *
     * @throws ContractViolation        When a contract does not hold
     * @throws InvalidContractException When a contract can not be compiled or returns a non-boolean value
     */
    public function check(MethodInvocation $invocation, array $contracts, array $variables, ContractKind $kind): void
    {
        $instance = $invocation->getThis();
        $location = $invocation->getMethod()->class . '::' . $invocation->getMethod()->name . '()';
        foreach ($contracts as $boundContract) {
            $expression = $boundContract->contract->expression;
            $evaluator  = $boundContract->check ?? $this->bindExpression($boundContract, $instance, $location);

            ++$this->depth;
            try {
                // A compiled contract takes the object first, then the variables by name
                $result = $boundContract->check !== null ? $evaluator($instance, ...$variables) : $evaluator($variables);
            } catch (Throwable $error) {
                // Assertion libraries signal failures with exceptions, broken expressions with errors
                throw $kind->createViolation($invocation, $expression, $error);
            } finally {
                --$this->depth;
            }

            if ($result === false) {
                throw $kind->createViolation($invocation, $expression);
            }
            // null is accepted for assertion helpers that return nothing
            if ($result !== true && $result !== null) {
                throw InvalidContractException::forInvalidResult($expression, $location, $result);
            }
        }
    }

    /**
     * Compiles the expression with eval() and binds it to the object, for contracts that were not compiled while
     * the class was woven
     *
     * @throws InvalidContractException When the expression is not valid PHP or uses $this in a static method
     */
    private function bindExpression(BoundContract $boundContract, ?object $instance, string $location): Closure
    {
        $expression = $boundContract->contract->expression;
        $isStatic   = $instance === null;
        if ($isStatic && \preg_match('/\$this\b/', $expression) === 1) {
            throw InvalidContractException::forStaticContext($expression, $location);
        }
        try {
            $closure = $this->compile($expression, $isStatic);
        } catch (ParseError $error) {
            throw InvalidContractException::forSyntaxError($expression, $location, $error);
        }
        $evaluator = Closure::bind($closure, $instance, $boundContract->scope);
        if ($evaluator === null) {
            throw InvalidContractException::forStaticContext($expression, $location);
        }

        return $evaluator;
    }

    /**
     * @return Closure(array<string, mixed>): mixed
     *
     * @throws ParseError When the expression is not valid PHP
     */
    private function compile(string $expression, bool $isStatic): Closure
    {
        $cacheKey = ($isStatic ? 'static:' : 'dynamic:') . $expression;
        if (isset($this->compiled[$cacheKey])) {
            return $this->compiled[$cacheKey];
        }
        // The closing parenthesis is on its own line, so a trailing "//" comment can not swallow it
        $code = 'return ' . ($isStatic ? 'static ' : '')
            . 'function (array $__contractVariables): mixed {'
            . ' \extract($__contractVariables, \EXTR_SKIP);'
            . ' return (' . $expression . "\n);"
            . ' };';

        $closure = eval($code);
        if (!$closure instanceof Closure) {
            throw new ParseError('The expression did not compile into a closure');
        }

        /** @var Closure(array<string, mixed>): mixed $closure */
        return $this->compiled[$cacheKey] = $closure;
    }
}
