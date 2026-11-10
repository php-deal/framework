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

namespace PhpDeal\Exception;

use Go\Aop\Framework\AbstractMethodInvocation;
use Go\Aop\Intercept\MethodInvocation;
use LogicException;
use ReflectionMethod;
use Throwable;
use UnitEnum;

/**
 * Base exception for every broken contract
 *
 * Catch {@see PreconditionViolation}, {@see PostconditionViolation} or {@see InvariantViolation} to handle a single
 * kind of contract. The file and line of the exception point to the method that declares the contract.
 */
class ContractViolation extends LogicException implements ExceptionInterface
{
    protected const string KIND = 'Contract';

    /**
     * Longest rendered argument, longer ones are truncated
     */
    private const int MAX_ARGUMENT_LENGTH = 40;

    /**
     * Expression of the violated contract
     */
    public readonly string $contract;

    /**
     * Name of the class whose method was called
     *
     * @var class-string
     */
    public readonly string $className;

    /**
     * Name of the called method
     */
    public readonly string $methodName;

    /**
     * @param MethodInvocation $invocation Invocation that violated the contract
     * @param string           $contract   Expression of the violated contract
     * @param Throwable|null   $previous   Error or assertion failure raised by the expression, if any
     */
    public function __construct(MethodInvocation $invocation, string $contract, ?Throwable $previous = null)
    {
        $method   = $invocation->getMethod();
        $instance = $invocation->getThis();

        $this->contract   = $contract;
        $this->className  = $instance !== null ? $instance::class : $invocation->getScope();
        $this->methodName = $method->name;

        $message = \sprintf(
            '%s "%s" violated in %s%s%s(%s)',
            static::KIND,
            $contract,
            $this->className,
            $instance !== null ? '->' : '::',
            $method->name,
            \implode(', ', \array_map(self::renderValue(...), $invocation->getArguments())),
        );
        if ($previous !== null) {
            $message .= ': ' . $previous->getMessage();
        }
        parent::__construct($message, 0, $previous);

        $this->pointToDeclaration($method);
    }

    /**
     * Moves the reported location to the method that declares the contract
     *
     * Woven classes run the original method body from a trait alias, so the alias holds the real source location.
     */
    private function pointToDeclaration(ReflectionMethod $method): void
    {
        $class     = $method->getDeclaringClass();
        $aliasName = $method->name . AbstractMethodInvocation::TRAIT_ALIAS_SUFFIX;
        $original  = $class->hasMethod($aliasName) ? $class->getMethod($aliasName) : $method;
        $fileName  = $original->getFileName();
        $line      = $original->getStartLine();

        if ($fileName !== false && $line !== false) {
            $this->file = $fileName;
            $this->line = $line;
        }
    }

    /**
     * Renders an argument for the exception message without exposing large or sensitive structures
     */
    private static function renderValue(mixed $value): string
    {
        $rendered = match (true) {
            $value === null                      => 'null',
            \is_bool($value)                     => $value ? 'true' : 'false',
            \is_int($value) || \is_float($value) => \var_export($value, true),
            \is_string($value)                   => "'" . \addcslashes($value, "'\\") . "'",
            \is_array($value)                    => 'array(' . \count($value) . ')',
            $value instanceof UnitEnum           => $value::class . '::' . $value->name,
            \is_object($value)                   => $value::class,
            default                              => \get_debug_type($value),
        };

        if (\strlen($rendered) <= self::MAX_ARGUMENT_LENGTH) {
            return $rendered;
        }
        // Cut at a character boundary when the value is valid UTF-8
        $limit = self::MAX_ARGUMENT_LENGTH - 3;
        if (\preg_match('/^.{0,' . $limit . '}/us', $rendered, $matches) === 1) {
            return $matches[0] . '...';
        }

        return \substr($rendered, 0, $limit) . '...';
    }
}
