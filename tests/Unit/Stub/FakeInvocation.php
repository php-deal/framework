<?php

declare(strict_types=1);

namespace PhpDeal\Tests\Unit\Stub;

use Go\Aop\Intercept\MethodInvocation;
use ReflectionMethod;

/**
 * Minimal method invocation for unit tests, without weaving
 *
 * @implements MethodInvocation<object, mixed>
 */
final class FakeInvocation implements MethodInvocation
{
    private ReflectionMethod $method;

    /**
     * @param class-string $className
     * @param list<mixed>  $arguments
     */
    public function __construct(
        private readonly ?object $instance,
        string $className,
        string $methodName,
        private array $arguments = [],
    ) {
        $this->method = new ReflectionMethod($className, $methodName);
    }

    public static function on(object $instance, string $methodName, mixed ...$arguments): self
    {
        return new self($instance, $instance::class, $methodName, \array_values($arguments));
    }

    /**
     * @param class-string $className
     */
    public static function onStatic(string $className, string $methodName, mixed ...$arguments): self
    {
        return new self(null, $className, $methodName, \array_values($arguments));
    }

    public function getMethod(): ReflectionMethod
    {
        return $this->method;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function setArguments(array $arguments): void
    {
        $this->arguments = $arguments;
    }

    public function proceed(): mixed
    {
        return $this->method->invokeArgs($this->instance, $this->arguments);
    }

    public function isDynamic(): bool
    {
        return $this->instance !== null;
    }

    public function getThis(): ?object
    {
        return $this->instance;
    }

    public function getScope(): string
    {
        return $this->method->class;
    }

    public function __toString(): string
    {
        return $this->method->class . '->' . $this->method->name;
    }
}
