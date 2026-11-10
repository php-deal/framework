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

use Go\Aop\Framework\AbstractMethodInvocation;
use Go\Aop\Proxy;
use Go\Core\AspectContainer;
use PhpDeal\Attribute\Ensure;
use PhpDeal\Attribute\Inherit;
use PhpDeal\Attribute\Invariant;
use PhpDeal\Attribute\Verify;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;

/**
 * Collects the contracts of methods and classes, including the ones inherited from parents and interfaces
 *
 * Results are cached per class and method, attributes are read only once per process. A cached answer is found
 * with one array lookup, before any reflection call.
 */
final class ContractFetcher
{
    /**
     * Pre-conditions by class and method name
     *
     * @var array<string, array<string, list<BoundContract>>>
     */
    private array $preconditions = [];

    /**
     * Post-conditions by class and method name
     *
     * @var array<string, array<string, list<BoundContract>>>
     */
    private array $postconditions = [];

    /**
     * Invariants by class name
     *
     * @var array<string, list<BoundContract>>
     */
    private array $invariants = [];

    /**
     * Returns the pre-conditions of a method
     *
     * Pre-conditions of parent classes and interfaces are included only when the method opts in, with
     * `#[Inherit]` on the method or its class, or with an `{@inheritdoc}` tag in its doc comment.
     *
     * @return list<BoundContract>
     */
    public function getPreconditions(ReflectionMethod $method): array
    {
        return $this->preconditions[$method->class][$method->name]
            ??= self::readMethodContracts($method, Verify::class, self::inheritsPreconditions($method));
    }

    /**
     * Returns the post-conditions of a method, together with the ones of parent classes and interfaces
     *
     * @return list<BoundContract>
     */
    public function getPostconditions(ReflectionMethod $method): array
    {
        return $this->postconditions[$method->class][$method->name]
            ??= self::readMethodContracts($method, Ensure::class, true);
    }

    /**
     * Returns the invariants of a class, together with the ones of parent classes and interfaces
     *
     * @param class-string $className
     *
     * @return list<BoundContract>
     */
    public function getInvariants(string $className): array
    {
        return $this->invariants[$className] ??= self::readInvariants(new ReflectionClass($className));
    }

    /**
     * @param ReflectionClass<object> $class
     *
     * @return list<BoundContract>
     */
    private static function readInvariants(ReflectionClass $class): array
    {
        $contracts = self::readContracts($class, Invariant::class, $class->name);
        foreach (self::getAncestors($class) as $ancestor) {
            $scope = $ancestor->isInterface() ? $class->name : $ancestor->name;
            \array_push($contracts, ...self::readContracts($ancestor, Invariant::class, $scope));
        }

        return self::unique($contracts);
    }

    /**
     * @param class-string<Verify|Ensure> $type Contract attribute to collect
     *
     * @return list<BoundContract>
     */
    private static function readMethodContracts(ReflectionMethod $method, string $type, bool $withAncestors): array
    {
        $class     = $method->getDeclaringClass();
        $contracts = self::readContracts($method, $type, $class->name);
        if ($withAncestors) {
            foreach (self::getAncestors($class) as $ancestor) {
                if (!$ancestor->hasMethod($method->name)) {
                    continue;
                }
                $ancestorMethod = $ancestor->getMethod($method->name);
                // A method declared higher in the hierarchy is read when that ancestor is visited
                if ($ancestorMethod->isPrivate() || $ancestorMethod->getDeclaringClass()->name !== $ancestor->name) {
                    continue;
                }
                $scope = $ancestor->isInterface() ? $class->name : $ancestor->name;
                \array_push($contracts, ...self::readContracts($ancestorMethod, $type, $scope));
            }
        }

        return self::unique($contracts);
    }

    private static function inheritsPreconditions(ReflectionMethod $method): bool
    {
        if ($method->getAttributes(Inherit::class) !== []) {
            return true;
        }
        if ($method->getDeclaringClass()->getAttributes(Inherit::class) !== []) {
            return true;
        }
        $docComment = $method->getDocComment();

        return $docComment !== false && \preg_match('/@inheritdoc\b/i', $docComment) === 1;
    }

    /**
     * Returns parent classes (closest first) and then every implemented interface
     *
     * @param ReflectionClass<covariant object> $class
     *
     * @return list<ReflectionClass<object>>
     */
    private static function getAncestors(ReflectionClass $class): array
    {
        $ancestors = [];
        $parent    = $class;
        while (($parent = $parent->getParentClass()) !== false) {
            $ancestors[] = $parent;
        }
        foreach ($class->getInterfaces() as $interface) {
            if ($interface->name !== Proxy::class) {
                $ancestors[] = $interface;
            }
        }

        return $ancestors;
    }

    /**
     * Only the built-in attributes are read: their expressions are constant expressions written in the source
     *
     * @template T of Verify|Ensure|Invariant
     *
     * @param ReflectionClass<covariant object>|ReflectionMethod $reflector
     * @param class-string<T>                                    $type
     * @param class-string                                       $scope
     *
     * @return list<BoundContract>
     */
    private static function readContracts(ReflectionClass|ReflectionMethod $reflector, string $type, string $scope): array
    {
        return \array_map(
            static fn(ReflectionAttribute $attribute): BoundContract => new BoundContract($attribute->newInstance(), $scope),
            self::getOriginalDeclaration($reflector)->getAttributes($type),
        );
    }

    /**
     * Returns the declaration written in the source, which holds the contracts compiled while weaving
     *
     * Go! AOP moves the body of a woven class into its `...OriginalTrait` and re-declares the attributes on the proxy
     * from the syntax tree, without the compiled closures. The trait and the `...OriginalAlias` methods keep them.
     *
     * @param ReflectionClass<covariant object>|ReflectionMethod $reflector
     *
     * @return ReflectionClass<covariant object>|ReflectionMethod
     */
    private static function getOriginalDeclaration(ReflectionClass|ReflectionMethod $reflector): ReflectionClass|ReflectionMethod
    {
        if ($reflector instanceof ReflectionClass) {
            return $reflector->getTraits()[$reflector->name . AspectContainer::ORIGINAL_TRAIT_SUFFIX] ?? $reflector;
        }
        $class     = $reflector->getDeclaringClass();
        $aliasName = $reflector->name . AbstractMethodInvocation::TRAIT_ALIAS_SUFFIX;
        if ($class->hasMethod($aliasName)) {
            $alias = $class->getMethod($aliasName);
            if ($alias->getDeclaringClass()->name === $class->name) {
                return $alias;
            }
        }

        return $reflector;
    }

    /**
     * Removes repeated expressions, the first (most specific) occurrence wins
     *
     * @param list<BoundContract> $contracts
     *
     * @return list<BoundContract>
     */
    private static function unique(array $contracts): array
    {
        $unique = [];
        foreach ($contracts as $boundContract) {
            $unique[$boundContract->contract::class . "\0" . $boundContract->contract->expression] ??= $boundContract;
        }

        return \array_values($unique);
    }
}
