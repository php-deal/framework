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

namespace PhpDeal\Contract\Fetcher\Parent;

use ReflectionClass;
use ReflectionException;

class MethodConditionWithInheritDocFetcher extends AbstractFetcher
{
    /**
     * Fetches conditions from all parent method prototypes recursively
     *
     * @param ReflectionClass $class
     * @param string          $methodName
     *
     * @return array
     * @throws ReflectionException
     */
    public function getConditions(ReflectionClass $class, string $methodName): array
    {
        $annotations   = [];
        $parentMethods = [];
        $docComment    = $class->getMethod($methodName)->getDocComment();

        if ($docComment === false) {
            return $annotations;
        }

        if (\preg_match('/\@inheritdoc/i', $docComment)) {
            $this->getParentClassesMethods($class, $methodName, $parentMethods);
            $this->getInterfacesMethods($class, $methodName, $parentMethods);
        }

        foreach ($parentMethods as $parentMethod) {
            $annotations[] = $this->annotationReader->getMethodAnnotations($parentMethod);
        }

        if (\count($annotations)) {
            $annotations = \array_merge(...$annotations);
        }

        return $this->filterContractAnnotation($annotations);
    }

    /**
     * @param ReflectionClass $class
     * @param string          $methodName
     * @param array           $parentMethods
     * @throws ReflectionException
     */
    private function getParentClassesMethods(ReflectionClass $class, string $methodName, array &$parentMethods): void
    {
        $parent = $class->getParentClass();

        if ($parent === false) {
            return;
        }

        if ($parent->hasMethod($methodName)) {
            $parentMethods[] = $parent->getMethod($methodName);
        }

        $this->getParentClassesMethods($parent, $methodName, $parentMethods);
    }

    /**
     * @param ReflectionClass $class
     * @param string          $methodName
     * @param array           $parentMethods
     * @throws ReflectionException
     */
    private function getInterfacesMethods(ReflectionClass $class, $methodName, &$parentMethods): void
    {
        foreach ($class->getInterfaces() as $interface) {
            if ($interface->hasMethod($methodName)) {
                $parentMethods[] = $interface->getMethod($methodName);
            }
        }
    }
}
