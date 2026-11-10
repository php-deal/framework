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

use Go\Aop\Proxy;
use PhpDeal\Attribute\Contract;
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
 * Results are cached per class and method, attributes are read only once per process.
 */
final class ContractFetcher
{
    /**
     * @var array<string, list<BoundContract>>
     */
    private array $cache = [];

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
        return $this->getMethodContracts($method, Verify::class, $this->inheritsPreconditions($method));
    }

    /**
     * Returns the post-conditions of a method, together with the ones of parent classes and interfaces
     *
     * @return list<BoundContract>
     */
    public function getPostconditions(ReflectionMethod $method): array
    {
        return $this->getMethodContracts($method, Ensure::class, true);
    }

    /**
     * Returns the invariants of a class, together with the ones of parent classes and interfaces
     *
     * @param ReflectionClass<covariant object> $class
     *
     * @return list<BoundContract>
     */
    public function getInvariants(ReflectionClass $class): array
    {
        $cacheKey = $class->name . '#' . Invariant::class;
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $contracts = self::readContracts($class, Invariant::class, $class->name);
        foreach (self::getAncestors($class) as $ancestor) {
            $scope = $ancestor->isInterface() ? $class->name : $ancestor->name;
            \array_push($contracts, ...self::readContracts($ancestor, Invariant::class, $scope));
        }

        return $this->cache[$cacheKey] = self::unique($contracts);
    }

    /**
     * @param class-string<Contract> $type Contract attribute to collect
     *
     * @return list<BoundContract>
     */
    private function getMethodContracts(ReflectionMethod $method, string $type, bool $withAncestors): array
    {
        $class    = $method->getDeclaringClass();
        $cacheKey = $class->name . '::' . $method->name . '#' . $type . ($withAncestors ? '+' : '');
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

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

        return $this->cache[$cacheKey] = self::unique($contracts);
    }

    private function inheritsPreconditions(ReflectionMethod $method): bool
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
     * @param ReflectionClass<covariant object>|ReflectionMethod $reflector
     * @param class-string<Contract>                             $type
     * @param class-string                                       $scope
     *
     * @return list<BoundContract>
     */
    private static function readContracts(ReflectionClass|ReflectionMethod $reflector, string $type, string $scope): array
    {
        return \array_map(
            static fn(ReflectionAttribute $attribute): BoundContract => new BoundContract($attribute->newInstance(), $scope),
            $reflector->getAttributes($type, ReflectionAttribute::IS_INSTANCEOF),
        );
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
