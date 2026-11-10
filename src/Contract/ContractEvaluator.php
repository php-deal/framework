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
use PhpToken;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use Throwable;
use UnitEnum;

/**
 * Evaluates contracts for a method invocation
 *
 * Contracts compiled while the class was woven (PHP 8.5+, see {@see ContractRewriter}) are called directly. Other
 * expressions are compiled with eval() into closures, once per process. While a contract is evaluated, contracts of
 * nested calls are not checked, so an invariant may call public methods of its own class without recursing endlessly.
 *
 * Everything that does not change between calls is cached per process: the parameters of a method, the closures
 * compiled with eval(), the static checks of an expression and whether a class can be cloned.
 */
final class ContractEvaluator
{
    /**
     * Closures compiled with eval(), for static and dynamic calls, by expression
     *
     * @var array{static: array<string, Closure(array<string, mixed>): mixed>, dynamic: array<string, Closure(array<string, mixed>): mixed>}
     */
    private array $compiled = ['static' => [], 'dynamic' => []];

    /**
     * Parameters of a method by class and method name: name, whether it is variadic, the default value, and the
     * parameter itself when its default must be evaluated on every call (it creates an object)
     *
     * @var array<string, array<string, list<array{string, bool, mixed, ?ReflectionParameter}>>>
     */
    private array $parameters = [];

    /**
     * Whether an expression uses the `$this` variable, by expression
     *
     * @var array<string, bool>
     */
    private array $usesThis = [];

    /**
     * Whether objects of a class can be cloned, by class name
     *
     * @var array<string, bool>
     */
    private array $cloneable = [];

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
        $method     = $invocation->getMethod();
        $parameters = $this->parameters[$method->class][$method->name] ??= self::describeParameters($method);
        $values     = $invocation->getArguments();
        $variables  = [];
        foreach ($parameters as $index => [$name, $isVariadic, $default, $lazyDefault]) {
            if ($isVariadic) {
                $variables[$name] = \array_slice($values, $index);
                break;
            }
            $variables[$name] = match (true) {
                \array_key_exists($index, $values) => $values[$index],
                $lazyDefault !== null              => $lazyDefault->getDefaultValue(),
                default                            => $default,
            };
        }

        return $variables;
    }

    /**
     * Returns a clone of the object for `$__old`, or null if objects of its class can not be cloned
     */
    public function cloneState(object $instance): ?object
    {
        $isCloneable = $this->cloneable[$instance::class] ??= new ReflectionClass($instance)->isCloneable();

        return $isCloneable ? clone $instance : null;
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
        foreach ($contracts as $boundContract) {
            $check     = $boundContract->check;
            $evaluator = $check ?? $this->bindExpression($boundContract, $invocation);

            ++$this->depth;
            try {
                // A compiled contract takes the object first, then the variables by name
                $result = $check !== null ? $check($instance, ...$variables) : $evaluator($variables);
            } catch (Throwable $error) {
                // Assertion libraries signal failures with exceptions, broken expressions with errors
                throw $kind->createViolation($invocation, $boundContract->contract->expression, $error);
            } finally {
                --$this->depth;
            }

            if ($result === false) {
                throw $kind->createViolation($invocation, $boundContract->contract->expression);
            }
            // null is accepted for assertion helpers that return nothing
            if ($result !== true && $result !== null) {
                throw InvalidContractException::forInvalidResult(
                    $boundContract->contract->expression,
                    self::describeLocation($invocation),
                    $result,
                );
            }
        }
    }

    /**
     * Compiles the expression with eval() and binds it to the object, for contracts that were not compiled while
     * the class was woven
     *
     * @return Closure(array<string, mixed>): mixed
     *
     * @throws InvalidContractException When the expression is not valid PHP or uses $this in a static method
     */
    private function bindExpression(BoundContract $boundContract, MethodInvocation $invocation): Closure
    {
        $expression = $boundContract->contract->expression;
        $instance   = $invocation->getThis();
        $isStatic   = $instance === null;
        if ($isStatic && ($this->usesThis[$expression] ??= self::usesThisVariable($expression))) {
            throw InvalidContractException::forStaticContext($expression, self::describeLocation($invocation));
        }
        try {
            $closure = $this->compile($expression, $isStatic);
        } catch (ParseError $error) {
            throw InvalidContractException::forSyntaxError($expression, self::describeLocation($invocation), $error);
        }
        $evaluator = Closure::bind($closure, $instance, $boundContract->scope);
        if ($evaluator === null) {
            throw InvalidContractException::forStaticContext($expression, self::describeLocation($invocation));
        }

        return $evaluator;
    }

    /**
     * Method that declares the contract, for error messages, e.g. "App\Account::deposit()"
     */
    private static function describeLocation(MethodInvocation $invocation): string
    {
        $method = $invocation->getMethod();

        return $method->class . '::' . $method->name . '()';
    }

    /**
     * Tells whether the expression reads the `$this` variable; `$this` inside a string literal does not count
     */
    private static function usesThisVariable(string $expression): bool
    {
        foreach (PhpToken::tokenize('<?php ' . $expression) as $token) {
            if ($token->is(\T_VARIABLE) && $token->text === '$this') {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<array{string, bool, mixed, ?ReflectionParameter}>
     */
    private static function describeParameters(ReflectionMethod $method): array
    {
        $parameters = [];
        foreach ($method->getParameters() as $parameter) {
            $default     = null;
            $lazyDefault = null;
            if (!$parameter->isVariadic() && $parameter->isDefaultValueAvailable()) {
                $default = $parameter->getDefaultValue();
                // A default created with `new` must not be shared between calls
                if (self::containsObject($default)) {
                    [$default, $lazyDefault] = [null, $parameter];
                }
            }
            $parameters[] = [$parameter->name, $parameter->isVariadic(), $default, $lazyDefault];
        }

        return $parameters;
    }

    private static function containsObject(mixed $value): bool
    {
        if (\is_array($value)) {
            return \array_any($value, self::containsObject(...));
        }

        // Enum cases are singletons, they can be shared
        return \is_object($value) && !$value instanceof UnitEnum;
    }

    /**
     * @return Closure(array<string, mixed>): mixed
     *
     * @throws ParseError When the expression is not valid PHP
     */
    private function compile(string $expression, bool $isStatic): Closure
    {
        $kind = $isStatic ? 'static' : 'dynamic';
        if (isset($this->compiled[$kind][$expression])) {
            return $this->compiled[$kind][$expression];
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
        return $this->compiled[$kind][$expression] = $closure;
    }
}
