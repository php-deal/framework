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

namespace PhpDeal\PHPStan;

use PhpDeal\Attribute\Ensure;
use PhpDeal\Attribute\Invariant;
use PhpDeal\Attribute\Verify;
use PHPStan\Reflection\AttributeReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Rules\Methods\AlwaysUsedMethodExtension;
use PHPStan\Rules\Properties\ReadWritePropertiesExtension;

/**
 * Tells PHPStan that private members used by contract expressions are used
 *
 * Contract expressions are strings, so without this extension PHPStan reports a private property that is only read
 * by `#[Invariant('$this->balance >= 0')]` as never read. Registered automatically by phpstan/extension-installer.
 */
final class ContractUsageExtension implements ReadWritePropertiesExtension, AlwaysUsedMethodExtension
{
    private const array CONTRACT_ATTRIBUTES = [Verify::class, Ensure::class, Invariant::class];

    /**
     * Contract expressions of each class, joined into one string
     *
     * @var array<string, string>
     */
    private array $contractCode = [];

    public function isAlwaysRead(ExtendedPropertyReflection $property, string $propertyName): bool
    {
        $name    = \preg_quote($propertyName, '/');
        $pattern = '/(?:\$this|\$__old)\s*\??->\s*' . $name . '\b|(?:self|static)::\$' . $name . '\b/';

        return \preg_match($pattern, $this->getContractCode($property->getDeclaringClass())) === 1;
    }

    public function isAlwaysWritten(ExtendedPropertyReflection $property, string $propertyName): bool
    {
        return false;
    }

    public function isInitialized(ExtendedPropertyReflection $property, string $propertyName): bool
    {
        return false;
    }

    public function isAlwaysUsed(MethodReflection $methodReflection): bool
    {
        $name    = \preg_quote($methodReflection->getName(), '/');
        $pattern = '/(?:\$this|\$__old)\s*\??->\s*' . $name . '\s*\(|(?:self|static)::' . $name . '\s*\(/i';

        return \preg_match($pattern, $this->getContractCode($methodReflection->getDeclaringClass())) === 1;
    }

    /**
     * Returns the contracts evaluated in the scope of the class: its own ones and the ones of its interfaces
     */
    private function getContractCode(ClassReflection $class): string
    {
        if (isset($this->contractCode[$class->getName()])) {
            return $this->contractCode[$class->getName()];
        }

        $expressions = [];
        foreach ([$class, ...\array_values($class->getInterfaces())] as $reflection) {
            \array_push($expressions, ...self::readExpressions($reflection->getAttributes()));
            foreach ($reflection->getNativeReflection()->getMethods() as $method) {
                $methodAttributes = $reflection->getNativeMethod($method->getName())->getAttributes();
                \array_push($expressions, ...self::readExpressions($methodAttributes));
            }
        }

        return $this->contractCode[$class->getName()] = \implode("\n", $expressions);
    }

    /**
     * @param list<AttributeReflection> $attributes
     *
     * @return list<string>
     */
    private static function readExpressions(array $attributes): array
    {
        $expressions = [];
        foreach ($attributes as $attribute) {
            if (!\in_array($attribute->getName(), self::CONTRACT_ATTRIBUTES, true)) {
                continue;
            }
            foreach ($attribute->getArgumentTypes() as $argumentType) {
                foreach ($argumentType->getConstantStrings() as $expression) {
                    $expressions[] = $expression->getValue();
                }
            }
        }

        return $expressions;
    }
}
